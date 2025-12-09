#!/usr/bin/env python3
"""
Bot de Telegram para Gestión de Gastos
"""

import logging
from telegram import Update
from telegram.ext import (
    Application,
    CommandHandler,
    MessageHandler,
    ConversationHandler,
    filters
)

from config import (
    TELEGRAM_BOT_TOKEN,
    API_URL,
    LOGIN_USERNAME,
    LOGIN_PASSWORD,
    LOGIN_2FA,
    NEW_MOVEMENT_TYPE,
    NEW_MOVEMENT_ACCOUNT,
    NEW_MOVEMENT_AMOUNT,
    NEW_MOVEMENT_NOTES,
    NEW_MOVEMENT_FILE
)

from handlers.auth_handlers import (
    start,
    help_command,
    login_start,
    login_username,
    login_password,
    login_2fa,
    logout,
    cancel
)

from handlers.query_handlers import (
    balance_command,
    accounts_command,
    account_detail_command,
    movements_command
)

from handlers.movement_handlers import (
    new_movement_start,
    new_movement_type,
    new_movement_account,
    new_movement_amount,
    new_movement_notes,
    new_movement_file,
    delete_movement_command,
    confirm_delete_movement,
    edit_movement_command
)

# Configurar logging
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)
logger = logging.getLogger(__name__)

def main():
    """Función principal del bot"""
    
    # Crear aplicación
    application = Application.builder().token(TELEGRAM_BOT_TOKEN).build()
    
    # ============================================
    # Conversación de Login
    # ============================================
    login_conversation = ConversationHandler(
        entry_points=[CommandHandler('login', login_start)],
        states={
            LOGIN_USERNAME: [MessageHandler(filters.TEXT & ~filters.COMMAND, login_username)],
            LOGIN_PASSWORD: [MessageHandler(filters.TEXT & ~filters.COMMAND, login_password)],
            LOGIN_2FA: [MessageHandler(filters.TEXT & ~filters.COMMAND, login_2fa)],
        },
        fallbacks=[CommandHandler('cancelar', cancel)]
    )
    
    # ============================================
    # Conversación de Nuevo Movimiento
    # ============================================
    new_movement_conversation = ConversationHandler(
        entry_points=[CommandHandler('nuevo', new_movement_start)],
        states={
            NEW_MOVEMENT_TYPE: [MessageHandler(filters.TEXT & ~filters.COMMAND, new_movement_type)],
            NEW_MOVEMENT_ACCOUNT: [MessageHandler(filters.TEXT & ~filters.COMMAND, new_movement_account)],
            NEW_MOVEMENT_AMOUNT: [MessageHandler(filters.TEXT & ~filters.COMMAND, new_movement_amount)],
            NEW_MOVEMENT_NOTES: [MessageHandler(filters.TEXT, new_movement_notes)],
            NEW_MOVEMENT_FILE: [
                MessageHandler(filters.Document.ALL | filters.PHOTO, new_movement_file),
                CommandHandler('omitir', new_movement_file)
            ],
        },
        fallbacks=[CommandHandler('cancelar', cancel)]
    )
    
    # ============================================
    # Comandos básicos
    # ============================================
    application.add_handler(CommandHandler('start', start))
    application.add_handler(CommandHandler('ayuda', help_command))
    application.add_handler(CommandHandler('help', help_command))
    
    # ============================================
    # Conversaciones
    # ============================================
    application.add_handler(login_conversation)
    application.add_handler(new_movement_conversation)
    
    # ============================================
    # Comandos de sesión
    # ============================================
    application.add_handler(CommandHandler('logout', logout))
    application.add_handler(CommandHandler('salir', logout))
    
    # ============================================
    # Comandos de consulta
    # ============================================
    application.add_handler(CommandHandler('balance', balance_command))
    application.add_handler(CommandHandler('cuentas', accounts_command))
    application.add_handler(CommandHandler('cuenta', account_detail_command))
    application.add_handler(CommandHandler('movimientos', movements_command))
    
    # ============================================
    # Comandos de modificación
    # ============================================
    application.add_handler(CommandHandler('eliminar', delete_movement_command))
    application.add_handler(CommandHandler('editar', edit_movement_command))
    
    # Handler para confirmación de eliminación
    application.add_handler(
        MessageHandler(
            filters.Regex(r'^(SI|NO)$') & ~filters.COMMAND,
            confirm_delete_movement
        )
    )
    
    # ============================================
    # Manejador de errores
    # ============================================
    async def error_handler(update: Update, context):
        """Maneja errores del bot"""
        logger.error(f"Error: {context.error}")
        if update and update.effective_message:
            await update.effective_message.reply_text(
                "❌ Ha ocurrido un error inesperado.\n"
                "Por favor, intenta de nuevo más tarde."
            )
    
    application.add_error_handler(error_handler)
    
    # ============================================
    # Iniciar bot
    # ============================================
    logger.info("🤖 Bot iniciado correctamente")
    logger.info(f"📡 Conectando a API: {API_URL}")
    
    # Iniciar polling
    application.run_polling(allowed_updates=Update.ALL_TYPES)

if __name__ == '__main__':
    main()