import os
from dotenv import load_dotenv

load_dotenv()

# Configuración del bot
TELEGRAM_BOT_TOKEN = os.getenv('TELEGRAM_BOT_TOKEN')
API_URL = os.getenv('API_URL', 'http://backend:80/api')

# Validar configuración
if not TELEGRAM_BOT_TOKEN:
    raise ValueError("TELEGRAM_BOT_TOKEN no está configurado en las variables de entorno")

# Mensajes del bot
MESSAGES = {
    'welcome': """
¡Bienvenido a Gestión de Gastos! 💰

Comandos disponibles:
/login - Iniciar sesión
/balance - Ver balance de tus cuentas
/cuentas - Listar tus cuentas
/movimientos - Ver últimos movimientos
/nuevo - Registrar nuevo movimiento
/ayuda - Ver todos los comandos
/logout - Cerrar sesión

Usa /login para comenzar.
    """,
    
    'help': """
📚 Comandos disponibles:

👤 Sesión:
/login - Iniciar sesión
/logout - Cerrar sesión

💰 Consultas:
/balance - Ver balance total
/cuentas - Listar cuentas
/movimientos - Últimos movimientos

✏️ Acciones:
/nuevo - Crear movimiento
/editar [id] - Editar movimiento
/eliminar [id] - Eliminar movimiento

ℹ️ Ayuda:
/ayuda - Ver esta ayuda
/cancelar - Cancelar operación actual
    """,
    
    'not_logged_in': "⚠️ No has iniciado sesión. Usa /login para comenzar.",
    'login_success': "✅ Sesión iniciada correctamente. Usa /ayuda para ver los comandos disponibles.",
    'logout_success': "👋 Sesión cerrada correctamente.",
    'operation_cancelled': "❌ Operación cancelada.",
    'error': "❌ Ha ocurrido un error. Por favor, intenta de nuevo.",
}

# Estados de conversación
(
    LOGIN_USERNAME,
    LOGIN_PASSWORD,
    LOGIN_2FA,
    NEW_MOVEMENT_TYPE,
    NEW_MOVEMENT_ACCOUNT,
    NEW_MOVEMENT_AMOUNT,
    NEW_MOVEMENT_NOTES,
    NEW_MOVEMENT_FILE,
    EDIT_MOVEMENT_FIELD,
    EDIT_MOVEMENT_VALUE,
) = range(10)