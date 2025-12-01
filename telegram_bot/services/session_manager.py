from typing import Dict, Optional
from services.api_client import APIClient

class SessionManager:
    """Gestor de sesiones de usuarios en el bot"""
    
    def __init__(self):
        self.sessions: Dict[int, Dict] = {}
    
    def create_session(self, user_id: int, token: str, user_data: Dict):
        """Crea una nueva sesión"""
        self.sessions[user_id] = {
            'token': token,
            'user': user_data,
            'api_client': APIClient(token)
        }
    
    def get_session(self, user_id: int) -> Optional[Dict]:
        """Obtiene la sesión de un usuario"""
        return self.sessions.get(user_id)
    
    def get_api_client(self, user_id: int) -> Optional[APIClient]:
        """Obtiene el cliente API de un usuario"""
        session = self.get_session(user_id)
        return session['api_client'] if session else None
    
    def is_logged_in(self, user_id: int) -> bool:
        """Verifica si un usuario tiene sesión activa"""
        return user_id in self.sessions
    
    def delete_session(self, user_id: int):
        """Elimina la sesión de un usuario"""
        if user_id in self.sessions:
            del self.sessions[user_id]
    
    def get_user_data(self, user_id: int) -> Optional[Dict]:
        """Obtiene los datos del usuario"""
        session = self.get_session(user_id)
        return session['user'] if session else None

# Instancia global del gestor de sesiones
session_manager = SessionManager()