from telegram import Update
from telegram.ext import ContextTypes, ConversationHandler
from services.api_client import APIClient
from services.session_manager import session_manager
from config import MESSAGES, LOGIN_USERNAME, LOGIN_PASSWORD, LOGIN_2FA

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Comando /start"""
    await update.message.reply_text(
        MESSAGES['welcome'],
        parse_mode='Markdown'
    )

async def help_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Comando /ayuda"""
    await update.message.reply_text(
        MESSAGES['help'],
        parse_mode='Markdown'
    )

async def login_start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Inicia el proceso de login"""
    user_id = update.effective_user.id
    
    if session_manager.is_logged_in(user_id):
        user_data = session_manager.get_user_data(user_id)
        await update.message.reply_text(
            f"Ya tienes sesión iniciada como *{user_data['nombre_usuario']}*.\n"
            "Usa /logout para cerrar sesión.",
            parse_mode='Markdown'
        )
        return ConversationHandler.END
    
    await update.message.reply_text(
        "🔐 *Inicio de Sesión*\n\n"
        "Ingresa tu nombre de usuario:",
        parse_mode='Markdown'
    )
    return LOGIN_USERNAME

async def login_username(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Recibe el nombre de usuario"""
    context.user_data['username'] = update.message.text.strip()
    
    await update.message.reply_text(
        "Ahora ingresa tu contraseña:"
    )
    return LOGIN_PASSWORD

async def login_password(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Recibe la contraseña e intenta hacer login"""
    username = context.user_data['username']
    password = update.message.text.strip()
    
    # Borrar mensaje con contraseña por seguridad
    try:
        await update.message.delete()
    except:
        pass
    
    try:
        api = APIClient()
        response = api.login(username, password)
        
        if response.get('data', {}).get('requires_2fa'):
            context.user_data['user_id_2fa'] = response['data']['user_id']
            await update.effective_message.reply_text(
                "🔐 Se ha enviado un código de verificación a tu correo.\n"
                "Ingresa el código (6 dígitos):"
            )
            return LOGIN_2FA
        
        # Login exitoso sin 2FA
        data = response['data']
        token = data['token']
        user_data = data['user']
        
        session_manager.create_session(
            update.effective_user.id,
            token,
            user_data
        )
        
        await update.effective_message.reply_text(
            f"✅ ¡Bienvenido, *{user_data['nombre_usuario']}*!\n\n"
            f"Usa /ayuda para ver los comandos disponibles.",
            parse_mode='Markdown'
        )
        
        return ConversationHandler.END
        
    except Exception as e:
        await update.effective_message.reply_text(
            f"❌ Error al iniciar sesión: {str(e)}\n\n"
            "Usa /login para intentar de nuevo."
        )
        return ConversationHandler.END

async def login_2fa(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Verifica el código 2FA"""
    code = update.message.text.strip()
    user_id_2fa = context.user_data.get('user_id_2fa')
    
    if not user_id_2fa:
        await update.message.reply_text(
            "❌ Error: sesión expirada. Usa /login para comenzar de nuevo."
        )
        return ConversationHandler.END
    
    try:
        api = APIClient()
        response = api.verify_2fa(user_id_2fa, code)
        
        data = response['data']
        token = data['token']
        user_data = data['user']
        
        session_manager.create_session(
            update.effective_user.id,
            token,
            user_data
        )
        
        await update.message.reply_text(
            f"✅ ¡Verificación exitosa!\n\n"
            f"Bienvenido, *{user_data['nombre_usuario']}*\n\n"
            f"Usa /ayuda para ver los comandos disponibles.",
            parse_mode='Markdown'
        )
        
        return ConversationHandler.END
        
    except Exception as e:
        await update.message.reply_text(
            f"❌ Código incorrecto o expirado: {str(e)}\n\n"
            "Usa /login para intentar de nuevo."
        )
        return ConversationHandler.END

async def logout(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Cierra la sesión del usuario"""
    user_id = update.effective_user.id
    
    if not session_manager.is_logged_in(user_id):
        await update.message.reply_text(MESSAGES['not_logged_in'])
        return
    
    try:
        api = session_manager.get_api_client(user_id)
        api.logout()
    except:
        pass
    
    session_manager.delete_session(user_id)
    
    await update.message.reply_text(MESSAGES['logout_success'])

async def cancel(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Cancela la operación actual"""
    await update.message.reply_text(MESSAGES['operation_cancelled'])
    return ConversationHandler.END