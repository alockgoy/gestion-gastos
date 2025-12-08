import { createContext, useContext, useState, useEffect } from 'react';
import { authAPI, userAPI } from '../services/api';
import toast from 'react-hot-toast';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth debe usarse dentro de AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    const token = localStorage.getItem('token');
    const storedUser = localStorage.getItem('user');

    if (!token || !storedUser) {
      setLoading(false);
      return;
    }

    try {
      const response = await authAPI.validateSession();
      if (response.data.success) {
        setUser(response.data.data.user);
        setIsAuthenticated(true);
      }
    } catch (error) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
      setIsAuthenticated(false);
    } finally {
      setLoading(false);
    }
  };

  const login = async (credentials) => {
    try {
      const response = await authAPI.login(credentials);

      if (response.data.data.requires_2fa) {
        return { requires2FA: true, userId: response.data.data.user_id };
      }

      const { token, user } = response.data.data;
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      setUser(user);
      setIsAuthenticated(true);
      toast.success('Sesión iniciada exitosamente');
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Error al iniciar sesión';
      toast.error(message);
      throw error;
    }
  };

  const verify2FA = async (userId, code) => {
    try {
      const response = await authAPI.verify2FA({ user_id: userId, codigo: code });
      const { token, user } = response.data.data;
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      setUser(user);
      setIsAuthenticated(true);
      toast.success('Verificación exitosa');
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Código incorrecto';
      toast.error(message);
      throw error;
    }
  };

  const register = async (data) => {
    try {
      const response = await authAPI.register(data);

      // Si el backend devuelve token, iniciar sesión automáticamente
      if (response.data.data.token) {
        const { token, user } = response.data.data;
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        setUser(user);
        setIsAuthenticated(true);
        toast.success('Cuenta creada exitosamente');
      } else {
        toast.success('Cuenta creada exitosamente. Ya puedes iniciar sesión.');
      }

      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Error al registrarse';
      toast.error(message);
      throw error;
    }
  };

  const logout = async () => {
    try {
      await authAPI.logout();
    } catch (error) {
      console.error('Error al cerrar sesión:', error);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      setUser(null);
      setIsAuthenticated(false);
      toast.success('Sesión cerrada');
    }
  };

  const updateUser = async () => {
    try {
      const response = await userAPI.getProfile();
      const updatedUser = response.data.data.user;
      setUser(updatedUser);
      localStorage.setItem('user', JSON.stringify(updatedUser));
    } catch (error) {
      console.error('Error al actualizar usuario:', error);
    }
  };

  const value = {
    user,
    loading,
    isAuthenticated,
    login,
    verify2FA,
    register,
    logout,
    updateUser,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};