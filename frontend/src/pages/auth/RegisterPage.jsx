import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { Input } from '../../components/common/Input';
import { Button } from '../../components/common/Button';

export const RegisterPage = () => {
  const [formData, setFormData] = useState({
    nombre_usuario: '',
    correo_electronico: '',
    contrasena: '',
    confirmar_contrasena: '',
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const { register } = useAuth();
  const navigate = useNavigate();

  const validate = () => {
    const newErrors = {};

    if (formData.nombre_usuario.length < 3) {
      newErrors.nombre_usuario = 'Mínimo 3 caracteres';
    }

    if (!/\S+@\S+\.\S+/.test(formData.correo_electronico)) {
      newErrors.correo_electronico = 'Email inválido';
    }

    if (formData.contrasena.length < 8) {
      newErrors.contrasena = 'Mínimo 8 caracteres';
    }

    if (!/[A-Z]/.test(formData.contrasena)) {
      newErrors.contrasena = 'Debe contener al menos una mayúscula';
    }

    if (!/[a-z]/.test(formData.contrasena)) {
      newErrors.contrasena = 'Debe contener al menos una minúscula';
    }

    if (!/[0-9]/.test(formData.contrasena)) {
      newErrors.contrasena = 'Debe contener al menos un número';
    }

    if (formData.contrasena !== formData.confirmar_contrasena) {
      newErrors.confirmar_contrasena = 'Las contraseñas no coinciden';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validate()) return;

    setLoading(true);

    try {
      const result = await register({
        nombre_usuario: formData.nombre_usuario,
        correo_electronico: formData.correo_electronico,
        contrasena: formData.contrasena,
      });
      
      if (result.success) {
        navigate('/login');
      }
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-600 to-secondary-600 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md fade-in">
        <div className="text-center mb-8">
          <h2 className="text-3xl font-bold text-gray-900">Crear Cuenta</h2>
          <p className="text-gray-600 mt-2">Comienza a gestionar tus finanzas</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Nombre de usuario"
            type="text"
            value={formData.nombre_usuario}
            onChange={(e) => setFormData({ ...formData, nombre_usuario: e.target.value })}
            error={errors.nombre_usuario}
            placeholder="usuario123"
            maxLength="50"
            required
          />

          <Input
            label="Correo electrónico"
            type="email"
            value={formData.correo_electronico}
            onChange={(e) => setFormData({ ...formData, correo_electronico: e.target.value })}
            error={errors.correo_electronico}
            placeholder="tu@email.com"
            maxLength="200"
            required
          />

          <Input
            label="Contraseña"
            type="password"
            value={formData.contrasena}
            onChange={(e) => setFormData({ ...formData, contrasena: e.target.value })}
            error={errors.contrasena}
            placeholder="••••••••"
            required
          />

          <Input
            label="Confirmar contraseña"
            type="password"
            value={formData.confirmar_contrasena}
            onChange={(e) => setFormData({ ...formData, confirmar_contrasena: e.target.value })}
            error={errors.confirmar_contrasena}
            placeholder="••••••••"
            required
          />

          <div className="text-xs text-gray-600 bg-gray-50 p-3 rounded-lg">
            <p className="font-medium mb-1">La contraseña debe contener:</p>
            <ul className="list-disc list-inside space-y-1">
              <li>Mínimo 8 caracteres</li>
              <li>Al menos una mayúscula</li>
              <li>Al menos una minúscula</li>
              <li>Al menos un número</li>
            </ul>
          </div>

          <Button
            type="submit"
            className="w-full"
            loading={loading}
          >
            Crear Cuenta
          </Button>
        </form>

        <div className="mt-6 text-center">
          <p className="text-sm text-gray-600">
            ¿Ya tienes cuenta?{' '}
            <Link
              to="/login"
              className="text-primary-600 hover:text-primary-700 font-medium"
            >
              Inicia sesión
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};