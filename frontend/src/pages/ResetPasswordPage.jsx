import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { authAPI } from '../services/api';
import { Button } from '../components/common/Button';
import { Input } from '../components/common/Input';
import { Card } from '../components/common/Card';
import { Lock } from 'lucide-react';
import toast from 'react-hot-toast';

export const ResetPasswordPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    nueva_contrasena: '',
    confirmar_contrasena: '',
  });

  const token = searchParams.get('token');

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (formData.nueva_contrasena !== formData.confirmar_contrasena) {
      toast.error('Las contraseñas no coinciden');
      return;
    }

    setLoading(true);

    try {
      await authAPI.resetPassword({
        token,
        nueva_contrasena: formData.nueva_contrasena,
      });
      toast.success('Contraseña actualizada');
      navigate('/login');
    } catch (error) {
      const message = error.response?.data?.message || 'Token inválido o expirado';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary-600 to-secondary-600 flex items-center justify-center p-4">
      <Card className="max-w-md w-full">
        <h2 className="text-2xl font-bold text-gray-900 text-center mb-6">
          Nueva contraseña
        </h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Nueva contraseña"
            type="password"
            value={formData.nueva_contrasena}
            onChange={(e) =>
              setFormData({ ...formData, nueva_contrasena: e.target.value })
            }
            required
          />
          <Input
            label="Confirmar contraseña"
            type="password"
            value={formData.confirmar_contrasena}
            onChange={(e) =>
              setFormData({ ...formData, confirmar_contrasena: e.target.value })
            }
            required
          />
          <Button type="submit" loading={loading} className="w-full">
            Restablecer contraseña
          </Button>
        </form>
      </Card>
    </div>
  );
};