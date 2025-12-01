import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { Button } from '../../components/common/Button';
import { Input } from '../../components/common/Input';
import { Mail, Lock, Github } from 'lucide-react';

export const LoginPage = () => {
  const [formData, setFormData] = useState({
    nombre_usuario: '',
    contrasena: '',
  });
  const [show2FA, setShow2FA] = useState(false);
  const [userId, setUserId] = useState(null);
  const [code2FA, setCode2FA] = useState('');
  const [loading, setLoading] = useState(false);
  const { login, verify2FA } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const result = await login(formData);
      
      if (result.requires2FA) {
        setShow2FA(true);
        setUserId(result.userId);
      } else if (result.success) {
        navigate('/dashboard');
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const handle2FASubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const result = await verify2FA(userId, code2FA);
      if (result.success) {
        navigate('/dashboard');
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  if (show2FA) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-primary-600 to-secondary-600 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md fade-in">
          <div className="text-center mb-8">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-primary-100 rounded-full mb-4">
              <Lock className="w-8 h-8 text-primary-600" />
            </div>
            <h2 className="text-2xl font-bold text-gray-900">Verificación 2FA</h2>
            <p className="text-gray-600 mt-2">
              Ingresa el código que enviamos a tu correo
            </p>
          </div>

          <form onSubmit={handle2FASubmit} className="space-y-4">
            <Input
              label="Código de verificación"
              type="text"
              maxLength="6"
              value={code2FA}
              onChange={(e) => setCode2FA(e.target.value)}
              placeholder="123456"
              required
            />

            <Button
              type="submit"
              className="w-full"
              loading={loading}
            >
              Verificar
            </Button>

            <button
              type="button"
              onClick={() => {
                setShow2FA(false);
                setCode2FA('');
                setUserId(null);
              }}
              className="w-full text-sm text-gray-600 hover:text-gray-900"
            >
              Volver al inicio de sesión
            </button>
          </form>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-600 to-secondary-600 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md fade-in">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-primary-600 to-secondary-600 rounded-2xl mb-4">
            <span className="text-white font-bold text-2xl">G</span>
          </div>
          <h2 className="text-3xl font-bold text-gray-900">Bienvenido</h2>
          <p className="text-gray-600 mt-2">Gestión de Gastos</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Nombre de usuario"
            type="text"
            value={formData.nombre_usuario}
            onChange={(e) => setFormData({ ...formData, nombre_usuario: e.target.value })}
            placeholder="Tu nombre de usuario"
            required
          />

          <Input
            label="Contraseña"
            type="password"
            value={formData.contrasena}
            onChange={(e) => setFormData({ ...formData, contrasena: e.target.value })}
            placeholder="••••••••"
            required
          />

          <div className="flex items-center justify-between text-sm">
            <Link
              to="/forgot-password"
              className="text-primary-600 hover:text-primary-700"
            >
              ¿Olvidaste tu contraseña?
            </Link>
          </div>

          <Button
            type="submit"
            className="w-full"
            loading={loading}
          >
            Iniciar Sesión
          </Button>
        </form>

        <div className="mt-6 text-center">
          <p className="text-sm text-gray-600">
            ¿No tienes cuenta?{' '}
            <Link
              to="/register"
              className="text-primary-600 hover:text-primary-700 font-medium"
            >
              Regístrate aquí
            </Link>
          </p>
        </div>

        <div className="mt-8 pt-6 border-t border-gray-200">
          <a
            href="https://github.com"
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center justify-center gap-2 text-sm text-gray-600 hover:text-gray-900"
          >
            <Github size={20} />
            Ver en GitHub
          </a>
        </div>
      </div>
    </div>
  );
};