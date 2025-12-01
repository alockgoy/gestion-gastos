from telegram import Update
from telegram.ext import ContextTypes
from services.session_manager import session_manager
from utils.formatters import (
    format_summary,
    format_accounts_list,
    format_movements_list,
    format_account
)
from config import MESSAGES

def require_login(func):
    """Decorador para verificar que el usuario esté logueado"""
    async def wrapper(update: Update, context: ContextTypes.DEFAULT_TYPE):
        user_id = update.effective_user.id
        
        if not session_manager.is_logged_in(user_id):
            await update.message.reply_text(MESSAGES['not_logged_in'])
            return
        
        return await func(update, context)
    
    return wrapper

@require_login
async def balance_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Comando /balance - Muestra el balance total"""
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    
    try:
        response = api.get_accounts_summary()
        summary = response['data']['summary']
        
        message = format_summary(summary)
        await update.message.reply_text(message, parse_mode='Markdown')
        
    except Exception as e:
        await update.message.reply_text(f"❌ Error: {str(e)}")

@require_login
async def accounts_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Comando /cuentas - Lista todas las cuentas"""
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    
    try:
        response = api.get_accounts()
        accounts = response['data']['cuentas']
        
        if not accounts:
            await update.message.reply_text(
                "No tienes cuentas registradas.\n"
                "Crea una desde la aplicación web."
            )
            return
        
        message = format_accounts_list(accounts)
        await update.message.reply_text(message, parse_mode='Markdown')
        
        # Preguntar si quiere ver detalles de alguna
        await update.message.reply_text(
            "Para ver detalles de una cuenta, usa:\n"
            "/cuenta [número]\n\n"
            "Ejemplo: /cuenta 1"
        )
        
    except Exception as e:
        await update.message.reply_text(f"❌ Error: {str(e)}")

@require_login
async def account_detail_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Comando /cuenta [id] - Muestra detalles de una cuenta"""
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    
    # Obtener ID de la cuenta
    if not context.args or not context.args[0].isdigit():
        await update.message.reply_text(
            "Uso: /cuenta [número]\n"
            "Ejemplo: /cuenta 1\n\n"
            "Usa /cuentas para ver la lista."
        )
        return
    
    try:
        # Primero obtener todas las cuentas para mapear el índice
        response = api.get_accounts()
        accounts = response['data']['cuentas']
        
        index = int(context.args[0]) - 1
        
        if index < 0 or index >= len(accounts):
            await update.message.reply_text(
                f"❌ Cuenta no encontrada. Tienes {len(accounts)} cuenta(s)."
            )
            return
        
        account = accounts[index]
        
        # Obtener detalles completos
        detail_response = api.get_account(account['id'])
        account_detail = detail_response['data']['cuenta']
        
        message = format_account(account_detail)
        await update.message.reply_text(message, parse_mode='Markdown')
        
    except Exception as e:
        await update.message.reply_text(f"❌ Error: {str(e)}")

@require_login
async def movements_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Comando /movimientos - Lista últimos movimientos"""
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    
    # Determinar límite
    limit = 10
    if context.args and context.args[0].isdigit():
        limit = min(int(context.args[0]), 50)
    
    try:
        response = api.get_movements(limit=limit)
        movements = response['data']['movimientos']
        
        if not movements:
            await update.message.reply_text(
                "No tienes movimientos registrados.\n"
                "Usa /nuevo para crear uno."
            )
            return
        
        message = format_movements_list(movements)
        await update.message.reply_text(message, parse_mode='Markdown')
        
        await update.message.reply_text(
            "Para editar o eliminar un movimiento:\n"
            "/editar [ID] - Editar movimiento\n"
            "/eliminar [ID] - Eliminar movimiento"
        )
        
    except Exception as e:
        await update.message.reply_text(f"❌ Error: {str(e)}")