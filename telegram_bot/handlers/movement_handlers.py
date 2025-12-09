from telegram import Update
from telegram.ext import ContextTypes, ConversationHandler
from services.session_manager import session_manager
from datetime import datetime
import os
from config import (
    MESSAGES,
    NEW_MOVEMENT_TYPE,
    NEW_MOVEMENT_ACCOUNT,
    NEW_MOVEMENT_AMOUNT,
    NEW_MOVEMENT_NOTES,
    NEW_MOVEMENT_FILE
)

def require_login(func):
    """Decorador para verificar que el usuario esté logueado"""
    async def wrapper(update: Update, context: ContextTypes.DEFAULT_TYPE):
        user_id = update.effective_user.id
        
        if not session_manager.is_logged_in(user_id):
            await update.message.reply_text(MESSAGES['not_logged_in'])
            return ConversationHandler.END
        
        return await func(update, context)
    
    return wrapper

@require_login
async def new_movement_start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Inicia el proceso de crear un movimiento"""
    await update.message.reply_text(
        "💰 *Nuevo Movimiento*\n\n"
        "¿Qué tipo de movimiento deseas registrar?\n\n"
        "1️⃣ Ingreso 📈\n"
        "2️⃣ Gasto 📉\n\n"
        "Responde con 1 o 2, o /cancelar para abortar.",
        parse_mode='Markdown'
    )
    return NEW_MOVEMENT_TYPE

@require_login
async def new_movement_type(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Recibe el tipo de movimiento"""
    tipo_input = update.message.text.strip()
    
    if tipo_input == '1':
        context.user_data['new_movement_tipo'] = 'ingreso'
        tipo_text = 'Ingreso 📈'
    elif tipo_input == '2':
        context.user_data['new_movement_tipo'] = 'retirada'
        tipo_text = 'Gasto 📉'
    else:
        await update.message.reply_text(
            "❌ Opción inválida. Responde con 1 (Ingreso) o 2 (Gasto)."
        )
        return NEW_MOVEMENT_TYPE
    
    # Obtener cuentas del usuario
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    
    try:
        response = api.get_accounts()
        accounts = response['data']['cuentas']
        
        if not accounts:
            await update.message.reply_text(
                "❌ No tienes cuentas registradas.\n"
                "Crea una cuenta desde la aplicación web primero."
            )
            return ConversationHandler.END
        
        context.user_data['accounts'] = accounts
        
        # Mostrar cuentas disponibles
        message = f"Tipo seleccionado: *{tipo_text}*\n\n"
        message += "Selecciona la cuenta:\n\n"
        
        for i, account in enumerate(accounts, 1):
            tipo_emoji = '💵' if account['tipo'] == 'efectivo' else '🏦'
            message += f"{i}. {tipo_emoji} {account['nombre']}\n"
        
        message += "\nResponde con el número de la cuenta."
        
        await update.message.reply_text(message, parse_mode='Markdown')
        return NEW_MOVEMENT_ACCOUNT
        
    except Exception as e:
        await update.message.reply_text(f"❌ Error: {str(e)}")
        return ConversationHandler.END

@require_login
async def new_movement_account(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Recibe la cuenta seleccionada"""
    account_input = update.message.text.strip()
    
    if not account_input.isdigit():
        await update.message.reply_text(
            "❌ Por favor, responde con el número de la cuenta."
        )
        return NEW_MOVEMENT_ACCOUNT
    
    index = int(account_input) - 1
    accounts = context.user_data.get('accounts', [])
    
    if index < 0 or index >= len(accounts):
        await update.message.reply_text(
            f"❌ Número inválido. Tienes {len(accounts)} cuenta(s)."
        )
        return NEW_MOVEMENT_ACCOUNT
    
    selected_account = accounts[index]
    context.user_data['new_movement_cuenta'] = selected_account['id']
    context.user_data['new_movement_cuenta_nombre'] = selected_account['nombre']
    
    await update.message.reply_text(
        f"Cuenta seleccionada: *{selected_account['nombre']}*\n\n"
        "Ahora ingresa la cantidad (solo números):\n"
        "Ejemplo: 50.00",
        parse_mode='Markdown'
    )
    return NEW_MOVEMENT_AMOUNT

@require_login
async def new_movement_amount(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Recibe la cantidad del movimiento"""
    amount_input = update.message.text.strip().replace(',', '.')
    
    try:
        amount = float(amount_input)
        if amount <= 0:
            raise ValueError("La cantidad debe ser mayor a 0")
        
        context.user_data['new_movement_cantidad'] = amount
        
        await update.message.reply_text(
            f"Cantidad: *€{amount:.2f}*\n\n"
            "¿Deseas agregar notas? (opcional)\n\n"
            "Escribe las notas o envía /omitir para continuar.",
            parse_mode='Markdown'
        )
        return NEW_MOVEMENT_NOTES
        
    except ValueError:
        await update.message.reply_text(
            "❌ Cantidad inválida. Ingresa un número positivo.\n"
            "Ejemplo: 50.00"
        )
        return NEW_MOVEMENT_AMOUNT

@require_login
async def new_movement_notes(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Recibe las notas del movimiento"""
    if update.message.text == '/omitir':
        context.user_data['new_movement_notas'] = ''
    else:
        context.user_data['new_movement_notas'] = update.message.text.strip()[:1000]
    
    await update.message.reply_text(
        "¿Deseas adjuntar un archivo? (PDF o imagen)\n\n"
        "Envía el archivo o /omitir para finalizar."
    )
    return NEW_MOVEMENT_FILE

@require_login
async def new_movement_file(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Recibe el archivo adjunto o finaliza sin él"""
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    file_path = None
    
    # Verificar si hay archivo
    if update.message.document:
        file = await update.message.document.get_file()
        file_path = f'/tmp/telegram_bot_{user_id}_{file.file_id}'
        await file.download_to_drive(file_path)
    elif update.message.photo:
        file = await update.message.photo[-1].get_file()
        file_path = f'/tmp/telegram_bot_{user_id}_{file.file_id}.jpg'
        await file.download_to_drive(file_path)
    
    # Crear movimiento
    try:
        data = {
            'tipo': context.user_data['new_movement_tipo'],
            'id_cuenta': context.user_data['new_movement_cuenta'],
            'cantidad': context.user_data['new_movement_cantidad'],
            'notas': context.user_data['new_movement_notas'],
            'fecha_movimiento': datetime.now().strftime('%Y-%m-%d')  # Solo fecha, sin hora
        }
        
        response = api.create_movement(data, file_path)
        
        # Limpiar archivo temporal
        if file_path and os.path.exists(file_path):
            os.remove(file_path)
        
        tipo_emoji = '📈' if data['tipo'] == 'ingreso' else '📉'
        tipo_text = 'Ingreso' if data['tipo'] == 'ingreso' else 'Gasto'
        
        await update.message.reply_text(
            f"✅ *{tipo_text} registrado exitosamente!*\n\n"
            f"{tipo_emoji} Cantidad: €{data['cantidad']:.2f}\n"
            f"Cuenta: {context.user_data['new_movement_cuenta_nombre']}\n\n"
            "Usa /movimientos para ver tu historial.",
            parse_mode='Markdown'
        )
        
        # Limpiar datos de contexto
        for key in list(context.user_data.keys()):
            if key.startswith('new_movement_'):
                del context.user_data[key]
        
        return ConversationHandler.END
        
    except Exception as e:
        if file_path and os.path.exists(file_path):
            os.remove(file_path)
        
        await update.message.reply_text(
            f"❌ Error al crear movimiento: {str(e)}\n\n"
            "Usa /nuevo para intentar de nuevo."
        )
        return ConversationHandler.END

@require_login
async def delete_movement_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Comando /eliminar [id] - Elimina un movimiento"""
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    
    if not context.args or not context.args[0].isdigit():
        await update.message.reply_text(
            "Uso: /eliminar [ID]\n"
            "Ejemplo: /eliminar 123\n\n"
            "Usa /movimientos para ver los IDs."
        )
        return
    
    movement_id = int(context.args[0])
    
    try:
        # Verificar que el movimiento existe y pertenece al usuario
        movement_response = api.get_movement(movement_id)
        movement = movement_response['data']['movimiento']
        
        # Confirmar eliminación
        tipo_emoji = '📈' if movement['tipo'] == 'ingreso' else '📉'
        
        await update.message.reply_text(
            f"⚠️ ¿Estás seguro de eliminar este movimiento?\n\n"
            f"{tipo_emoji} ID: {movement_id}\n"
            f"Cantidad: €{movement['cantidad']:.2f}\n"
            f"Cuenta: {movement.get('cuenta_nombre', 'N/A')}\n\n"
            "Responde SI para confirmar o NO para cancelar."
        )
        
        context.user_data['delete_movement_id'] = movement_id
        
    except Exception as e:
        await update.message.reply_text(f"❌ Error: {str(e)}")

@require_login
async def confirm_delete_movement(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Confirma la eliminación del movimiento"""
    user_id = update.effective_user.id
    api = session_manager.get_api_client(user_id)
    
    response = update.message.text.strip().upper()
    movement_id = context.user_data.get('delete_movement_id')
    
    if not movement_id:
        return
    
    if response == 'SI':
        try:
            api.delete_movement(movement_id)
            await update.message.reply_text(
                f"✅ Movimiento {movement_id} eliminado correctamente.\n"
                "El balance se ha actualizado automáticamente gracias a los triggers de la base de datos."
            )
        except Exception as e:
            await update.message.reply_text(f"❌ Error: {str(e)}")
    else:
        await update.message.reply_text("❌ Eliminación cancelada.")
    
    if 'delete_movement_id' in context.user_data:
        del context.user_data['delete_movement_id']