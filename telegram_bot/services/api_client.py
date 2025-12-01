import requests
from typing import Optional, Dict, Any
from config import API_URL

class APIClient:
    """Cliente para interactuar con la API REST"""
    
    def __init__(self, token: Optional[str] = None):
        self.base_url = API_URL
        self.token = token
        self.headers = {
            'Content-Type': 'application/json'
        }
        if token:
            self.headers['Authorization'] = f'Bearer {token}'
    
    def set_token(self, token: str):
        """Establece el token de autenticación"""
        self.token = token
        self.headers['Authorization'] = f'Bearer {token}'
    
    def clear_token(self):
        """Elimina el token de autenticación"""
        self.token = None
        if 'Authorization' in self.headers:
            del self.headers['Authorization']
    
    def _request(self, method: str, endpoint: str, **kwargs) -> Dict[str, Any]:
        """Realiza una petición HTTP"""
        url = f"{self.base_url}{endpoint}"
        
        try:
            response = requests.request(
                method=method,
                url=url,
                headers=self.headers,
                timeout=30,
                **kwargs
            )
            
            if response.status_code == 401:
                raise Exception("Sesión expirada o inválida")
            
            response.raise_for_status()
            return response.json()
        
        except requests.exceptions.RequestException as e:
            raise Exception(f"Error de conexión: {str(e)}")
    
    # AUTH
    def login(self, username: str, password: str) -> Dict[str, Any]:
        """Inicia sesión"""
        return self._request('POST', '/auth/login', json={
            'nombre_usuario': username,
            'contrasena': password
        })
    
    def verify_2fa(self, user_id: int, code: str) -> Dict[str, Any]:
        """Verifica código 2FA"""
        return self._request('POST', '/auth/verify-2fa', json={
            'user_id': user_id,
            'codigo': code
        })
    
    def logout(self) -> Dict[str, Any]:
        """Cierra sesión"""
        return self._request('POST', '/auth/logout')
    
    # USER
    def get_profile(self) -> Dict[str, Any]:
        """Obtiene perfil del usuario"""
        return self._request('GET', '/user/profile')
    
    # ACCOUNTS
    def get_accounts(self) -> Dict[str, Any]:
        """Obtiene lista de cuentas"""
        return self._request('GET', '/accounts')
    
    def get_account(self, account_id: int) -> Dict[str, Any]:
        """Obtiene una cuenta específica"""
        return self._request('GET', f'/accounts/{account_id}')
    
    def get_accounts_summary(self) -> Dict[str, Any]:
        """Obtiene resumen de cuentas"""
        return self._request('GET', '/accounts/summary')
    
    # MOVEMENTS
    def get_movements(self, limit: int = 10, **filters) -> Dict[str, Any]:
        """Obtiene lista de movimientos"""
        params = {'limit': limit, **filters}
        return self._request('GET', '/movements', params=params)
    
    def get_movement(self, movement_id: int) -> Dict[str, Any]:
        """Obtiene un movimiento específico"""
        return self._request('GET', f'/movements/{movement_id}')
    
    def create_movement(self, data: Dict[str, Any], file_path: Optional[str] = None) -> Dict[str, Any]:
        """Crea un nuevo movimiento"""
        if file_path:
            # Si hay archivo, usar multipart/form-data
            files = {'adjunto': open(file_path, 'rb')}
            headers = {k: v for k, v in self.headers.items() if k != 'Content-Type'}
            
            response = requests.post(
                f"{self.base_url}/movements",
                data=data,
                files=files,
                headers=headers,
                timeout=30
            )
            
            if file_path:
                files['adjunto'].close()
            
            response.raise_for_status()
            return response.json()
        else:
            return self._request('POST', '/movements', json=data)
    
    def update_movement(self, movement_id: int, data: Dict[str, Any], 
                       file_path: Optional[str] = None) -> Dict[str, Any]:
        """Actualiza un movimiento"""
        if file_path:
            files = {'adjunto': open(file_path, 'rb')}
            headers = {k: v for k, v in self.headers.items() if k != 'Content-Type'}
            
            response = requests.put(
                f"{self.base_url}/movements/{movement_id}",
                data=data,
                files=files,
                headers=headers,
                timeout=30
            )
            
            if file_path:
                files['adjunto'].close()
            
            response.raise_for_status()
            return response.json()
        else:
            return self._request('PUT', f'/movements/{movement_id}', json=data)
    
    def delete_movement(self, movement_id: int) -> Dict[str, Any]:
        """Elimina un movimiento"""
        return self._request('DELETE', f'/movements/{movement_id}')
    
    def get_movements_stats(self, **filters) -> Dict[str, Any]:
        """Obtiene estadísticas de movimientos"""
        return self._request('GET', '/movements/stats', params=filters)