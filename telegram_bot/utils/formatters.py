from datetime import datetime
from typing import Dict, List

def format_money(amount, currency: str = 'EUR') -> str:
    """Formatea cantidad de dinero"""
    # Asegurar que amount sea float
    if isinstance(amount, str):
        amount = float(amount)
    
    return f"{amount:,.2f} {currency}".replace(',', 'X').replace('.', ',').replace('X', '.')

def format_date(date_str: str) -> str:
    """Formatea fecha a formato legible (solo fecha, sin hora)"""
    try:
        dt = datetime.fromisoformat(date_str.replace('Z', '+00:00'))
        return dt.strftime('%d/%m/%Y')
    except:
        return date_str

def format_account(account: Dict) -> str:
    """Formatea información de una cuenta"""
    tipo_emoji = '💵' if account['tipo'] == 'efectivo' else '🏦'
    balance = format_money(account['balance'])
    
    msg = f"{tipo_emoji} *{account['nombre']}*\n"
    msg += f"Balance: `{balance}`\n"
    
    if account.get('etiqueta_nombre'):
        msg += f"Etiqueta: {account['etiqueta_nombre']}\n"
    
    if account.get('meta'):
        meta = format_money(account['meta'])
        msg += f"Meta: {meta}\n"
        
        if account.get('progreso_meta'):
            progreso = account['progreso_meta']
            porcentaje = float(progreso['porcentaje']) if isinstance(progreso['porcentaje'], str) else progreso['porcentaje']
            msg += f"Progreso: {porcentaje:.1f}%\n"
    
    return msg

def format_movement(movement: Dict) -> str:
    """Formatea información de un movimiento"""
    tipo_emoji = '📈' if movement['tipo'] == 'ingreso' else '📉'
    tipo_text = 'Ingreso' if movement['tipo'] == 'ingreso' else 'Gasto'
    
    cantidad = format_money(movement['cantidad'])
    fecha = format_date(movement['fecha_movimiento'])
    
    msg = f"{tipo_emoji} *{tipo_text}*\n"
    msg += f"ID: `{movement['id']}`\n"
    msg += f"Cuenta: {movement.get('cuenta_nombre', 'N/A')}\n"
    msg += f"Cantidad: `{cantidad}`\n"
    msg += f"Fecha: {fecha}\n"
    
    if movement.get('notas'):
        msg += f"Notas: {movement['notas'][:100]}\n"
    
    if movement.get('adjunto'):
        msg += f"📎 Archivo adjunto\n"
    
    return msg

def format_accounts_list(accounts: List[Dict]) -> str:
    """Formatea lista de cuentas"""
    if not accounts:
        return "No tienes cuentas registradas."
    
    msg = "💰 *Tus cuentas:*\n\n"
    
    for i, account in enumerate(accounts, 1):
        tipo_emoji = '💵' if account['tipo'] == 'efectivo' else '🏦'
        balance = format_money(account['balance'])
        msg += f"{i}. {tipo_emoji} {account['nombre']}: `{balance}`\n"
    
    return msg

def format_movements_list(movements: List[Dict]) -> str:
    """Formatea lista de movimientos"""
    if not movements:
        return "No hay movimientos registrados."
    
    msg = "📊 *Últimos movimientos:*\n\n"
    
    for movement in movements:
        tipo_emoji = '📈' if movement['tipo'] == 'ingreso' else '📉'
        cantidad = format_money(movement['cantidad'])
        fecha = format_date(movement['fecha_movimiento'])
        
        msg += f"{tipo_emoji} `{cantidad}` - {movement.get('cuenta_nombre', 'N/A')}\n"
        msg += f"   {fecha} (ID: {movement['id']})\n\n"
    
    return msg

def format_summary(summary: Dict) -> str:
    """Formatea resumen de cuentas"""
    balance_total = format_money(summary.get('balance_total', 0))
    total_cuentas = summary.get('total_cuentas', 0)
    
    msg = "💼 *Resumen Financiero*\n\n"
    msg += f"Balance Total: `{balance_total}`\n"
    msg += f"Cuentas: {total_cuentas}\n"
    
    return msg