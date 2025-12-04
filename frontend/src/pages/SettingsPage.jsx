import { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { userAPI } from '../services/api';
import { Card } from '../components/common/Card';
import { Button } from '../components/common/Button';
import { Input } from '../components/common/Input';
import { Badge } from '../components/common/Badge';
import { ConfirmDialog } from '../components/common/ConfirmDialog';
import { Modal } from '../components/common/Modal';
import {
  User,
  Mail,
  Lock,
  Shield,
  Camera,
  Trash2,
  Key,
  LogOut,
} from 'lucide-react';
import toast from 'react-hot-toast';

export const SettingsPage = () => {
  const { user, updateUser, logout } = useAuth();
  const [activeTab, setActiveTab] = useState('profile');
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);

  const tabs = [
    { id: 'profile', name: 'Perfil', icon: User },
    { id: 'security', name: 'Seguridad', icon: Shield },
    { id: 'api', name: 'API Tokens', icon: Key },
    { id: 'danger', name: 'Zona de Peligro', icon: Trash2 },
  ];

  return (
    <div className="space-y-6 fade-in">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Configuración</h1>
        <p className="text-gray-600 mt-1">
          Administra tu cuenta y preferencias
        </p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="flex gap-8 overflow-x-auto">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors ${
                activeTab === tab.id
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <tab.icon size={20} />
              {tab.name}
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      {activeTab === 'profile' && <ProfileTab user={user} updateUser={updateUser} />}
      {activeTab === 'security' && <SecurityTab user={user} updateUser={updateUser} />}
      {activeTab === 'api' && <APITokensTab />}
      {activeTab === 'danger' && (
        <DangerZoneTab
          onDeleteAccount={() => setIsDeleteDialogOpen(true)}
        />
      )}

      {/* Delete Account Dialog */}
      <DeleteAccountDialog
        isOpen={isDeleteDialogOpen}
        onClose={() => setIsDeleteDialogOpen(false)}
        onConfirm={logout}
      />
    </div>
  );
};

// Profile Tab
const ProfileTab = ({ user, updateUser }) => {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    nombre_usuario: user?.nombre_usuario || '',
    correo_electronico: user?.correo_electronico || '',
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await userAPI.updateProfile(formData);
      await updateUser();
      toast.success('Perfil actualizado');
    } catch (error) {
      const message = error.response?.data?.message || 'Error al actualizar';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handlePhotoUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('foto', file);

    try {
      await userAPI.uploadProfilePhoto(formData);
      await updateUser();
      toast.success('Foto actualizada');
    } catch (error) {
      const message = error.response?.data?.message || 'Error al subir foto';
      toast.error(message);
    }
  };

  return (
    <div className="space-y-6">
      <Card>
        <h2 className="text-xl font-semibold text-gray-900 mb-4">
          Información Personal
        </h2>
        
        {/* Profile Photo */}
        <div className="flex items-center gap-6 mb-6 pb-6 border-b border-gray-200">
          <div className="relative">
            {user?.foto_perfil ? (
              <img
                src={`/uploads/profiles/${user.foto_perfil}`}
                alt="Perfil"
                className="w-20 h-20 rounded-full object-cover"
              />
            ) : (
              <div className="w-20 h-20 rounded-full bg-primary-100 flex items-center justify-center">
                <User className="w-10 h-10 text-primary-600" />
              </div>
            )}
            <label
              htmlFor="photo-upload"
              className="absolute bottom-0 right-0 p-1.5 bg-white rounded-full border-2 border-white shadow-lg cursor-pointer hover:bg-gray-50"
            >
              <Camera size={16} className="text-gray-600" />
            </label>
            <input
              type="file"
              id="photo-upload"
              className="hidden"
              accept="image/*"
              onChange={handlePhotoUpload}
            />
          </div>
          <div>
            <h3 className="font-medium text-gray-900">{user?.nombre_usuario}</h3>
            <p className="text-sm text-gray-600">{user?.correo_electronico}</p>
            <Badge variant="primary" className="mt-2">
              {user?.rol}
            </Badge>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <Input
            label="Nombre de usuario"
            type="text"
            value={formData.nombre_usuario}
            onChange={(e) =>
              setFormData({ ...formData, nombre_usuario: e.target.value })
            }
            maxLength="50"
            required
          />

          <Input
            label="Correo electrónico"
            type="email"
            value={formData.correo_electronico}
            onChange={(e) =>
              setFormData({ ...formData, correo_electronico: e.target.value })
            }
            maxLength="200"
            required
          />

          <Button type="submit" loading={loading}>
            Guardar cambios
          </Button>
        </form>
      </Card>
    </div>
  );
};

// Security Tab
const SecurityTab = ({ user, updateUser }) => {
  const [loading, setLoading] = useState(false);
  const [passwordForm, setPasswordForm] = useState({
    contrasena_actual: '',
    contrasena_nueva: '',
    confirmar_contrasena: '',
  });

  const handlePasswordChange = async (e) => {
    e.preventDefault();

    if (passwordForm.contrasena_nueva !== passwordForm.confirmar_contrasena) {
      toast.error('Las contraseñas no coinciden');
      return;
    }

    setLoading(true);

    try {
      await userAPI.changePassword({
        contrasena_actual: passwordForm.contrasena_actual,
        contrasena_nueva: passwordForm.contrasena_nueva,
      });
      setPasswordForm({
        contrasena_actual: '',
        contrasena_nueva: '',
        confirmar_contrasena: '',
      });
      toast.success('Contraseña actualizada');
    } catch (error) {
      const message = error.response?.data?.message || 'Error al cambiar contraseña';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const handle2FAToggle = async () => {
    try {
      const password = prompt('Ingresa tu contraseña para continuar:');
      if (!password) return;

      await userAPI.toggle2FA({
        enable: !user.autenticacion_2fa,
        contrasena: password,
      });
      await updateUser();
      toast.success(
        user.autenticacion_2fa
          ? '2FA desactivado'
          : '2FA activado'
      );
    } catch (error) {
      const message = error.response?.data?.message || 'Error al cambiar 2FA';
      toast.error(message);
    }
  };

  return (
    <div className="space-y-6">
      {/* 2FA */}
      <Card>
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-gray-900">
              Verificación en 2 pasos
            </h3>
            <p className="text-sm text-gray-600 mt-1">
              Añade una capa extra de seguridad a tu cuenta
            </p>
            <Badge
              variant={user?.autenticacion_2fa ? 'success' : 'warning'}
              className="mt-2"
            >
              {user?.autenticacion_2fa ? 'Activado' : 'Desactivado'}
            </Badge>
          </div>
          <Button
            variant={user?.autenticacion_2fa ? 'danger' : 'primary'}
            onClick={handle2FAToggle}
          >
            {user?.autenticacion_2fa ? 'Desactivar' : 'Activar'}
          </Button>
        </div>
      </Card>

      {/* Change Password */}
      <Card>
        <h2 className="text-xl font-semibold text-gray-900 mb-4">
          Cambiar Contraseña
        </h2>
        <form onSubmit={handlePasswordChange} className="space-y-4">
          <Input
            label="Contraseña actual"
            type="password"
            value={passwordForm.contrasena_actual}
            onChange={(e) =>
              setPasswordForm({
                ...passwordForm,
                contrasena_actual: e.target.value,
              })
            }
            required
          />

          <Input
            label="Nueva contraseña"
            type="password"
            value={passwordForm.contrasena_nueva}
            onChange={(e) =>
              setPasswordForm({
                ...passwordForm,
                contrasena_nueva: e.target.value,
              })
            }
            required
          />

          <Input
            label="Confirmar nueva contraseña"
            type="password"
            value={passwordForm.confirmar_contrasena}
            onChange={(e) =>
              setPasswordForm({
                ...passwordForm,
                confirmar_contrasena: e.target.value,
              })
            }
            required
          />

          <Button type="submit" loading={loading}>
            Cambiar contraseña
          </Button>
        </form>
      </Card>
    </div>
  );
};

// API Tokens Tab
const APITokensTab = () => {
  const [tokens, setTokens] = useState([]);
  const [loading, setLoading] = useState(false);
  const [newTokenName, setNewTokenName] = useState('');
  const [generatedToken, setGeneratedToken] = useState(null);

  useEffect(() => {
    loadTokens();
  }, []);

  const loadTokens = async () => {
    try {
      const response = await userAPI.listAPITokens();
      setTokens(response.data.data.tokens);
    } catch (error) {
      toast.error('Error al cargar tokens');
    }
  };

  const handleGenerate = async () => {
    if (!newTokenName.trim()) {
      toast.error('Ingresa un nombre para el token');
      return;
    }

    setLoading(true);

    try {
      const response = await userAPI.generateAPIToken({ nombre: newTokenName });
      setGeneratedToken(response.data.data.token);
      setNewTokenName('');
      await loadTokens();
      toast.success('Token generado');
    } catch (error) {
      toast.error('Error al generar token');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (tokenId) => {
    if (!confirm('¿Eliminar este token?')) return;

    try {
      await userAPI.deleteAPIToken({ token_id: tokenId });
      await loadTokens();
      toast.success('Token eliminado');
    } catch (error) {
      toast.error('Error al eliminar token');
    }
  };

  return (
    <div className="space-y-6">
      <Card>
        <h2 className="text-xl font-semibold text-gray-900 mb-4">
          Tokens de API
        </h2>
        <p className="text-sm text-gray-600 mb-4">
          Usa estos tokens para acceder a la API desde aplicaciones externas
        </p>

        {generatedToken && (
          <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <p className="text-sm font-medium text-green-900 mb-2">
              ✅ Token generado. Guárdalo en un lugar seguro:
            </p>
            <code className="block p-3 bg-white rounded border border-green-300 text-sm break-all">
              {generatedToken}
            </code>
            <p className="text-xs text-green-800 mt-2">
              No podrás volver a ver este token
            </p>
          </div>
        )}

        <div className="flex gap-3 mb-6">
          <Input
            placeholder="Nombre del token"
            value={newTokenName}
            onChange={(e) => setNewTokenName(e.target.value)}
          />
          <Button onClick={handleGenerate} loading={loading}>
            Generar
          </Button>
        </div>

        <div className="space-y-3">
          {tokens.map((token) => (
            <div
              key={token.id}
              className="flex items-center justify-between p-4 border border-gray-200 rounded-lg"
            >
              <div>
                <p className="font-medium text-gray-900">{token.nombre}</p>
                <p className="text-sm text-gray-600">
                  <code>{token.token_preview}</code>
                </p>
                <p className="text-xs text-gray-500 mt-1">
                  Creado: {new Date(token.created_at).toLocaleDateString()}
                </p>
              </div>
              <Button
                variant="danger"
                size="sm"
                onClick={() => handleDelete(token.id)}
              >
                <Trash2 size={16} />
              </Button>
            </div>
          ))}
          {tokens.length === 0 && (
            <p className="text-center text-gray-500 py-8">
              No tienes tokens creados
            </p>
          )}
        </div>
      </Card>
    </div>
  );
};

// Danger Zone Tab
const DangerZoneTab = ({ onDeleteAccount }) => {
  return (
    <Card className="border-2 border-red-200">
      <div className="flex items-start gap-4">
        <div className="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
          <Trash2 className="w-6 h-6 text-red-600" />
        </div>
        <div className="flex-1">
          <h3 className="text-lg font-semibold text-red-900">Eliminar cuenta</h3>
          <p className="text-sm text-red-700 mt-1">
            Esta acción es irreversible. Se eliminarán todos tus datos, cuentas y
            movimientos permanentemente.
          </p>
          <Button
            variant="danger"
            onClick={onDeleteAccount}
            className="mt-4"
          >
            Eliminar mi cuenta
          </Button>
        </div>
      </div>
    </Card>
  );
};

// Delete Account Dialog
const DeleteAccountDialog = ({ isOpen, onClose, onConfirm }) => {
  const [password, setPassword] = useState('');
  const [confirm1, setConfirm1] = useState('');
  const [confirm2, setConfirm2] = useState('');
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    if (confirm1 !== 'ELIMINAR' || confirm2 !== 'ELIMINAR') {
      toast.error('Debes escribir ELIMINAR en ambos campos');
      return;
    }

    setLoading(true);

    try {
      await userAPI.deleteAccount({
        contrasena: password,
        confirmacion1: confirm1,
        confirmacion2: confirm2,
      });
      toast.success('Cuenta eliminada');
      onConfirm();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al eliminar cuenta';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Eliminar cuenta" size="md">
      <div className="space-y-4">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <p className="text-sm font-medium text-red-900">
            ⚠️ Esta acción es irreversible
          </p>
          <p className="text-sm text-red-800 mt-2">
            Se eliminarán permanentemente:
          </p>
          <ul className="text-sm text-red-800 list-disc list-inside mt-2 space-y-1">
            <li>Tu cuenta de usuario</li>
            <li>Todas tus cuentas bancarias</li>
            <li>Todos tus movimientos</li>
            <li>Archivos adjuntos</li>
          </ul>
        </div>

        <Input
          label="Contraseña"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          placeholder="Tu contraseña actual"
          required
        />

        <Input
          label="Escribe ELIMINAR"
          type="text"
          value={confirm1}
          onChange={(e) => setConfirm1(e.target.value)}
          placeholder="ELIMINAR"
          required
        />

        <Input
          label="Escribe ELIMINAR nuevamente"
          type="text"
          value={confirm2}
          onChange={(e) => setConfirm2(e.target.value)}
          placeholder="ELIMINAR"
          required
        />

        <div className="flex gap-3 pt-4 border-t border-gray-200">
          <Button variant="secondary" onClick={onClose} className="flex-1">
            Cancelar
          </Button>
          <Button
            variant="danger"
            onClick={handleDelete}
            loading={loading}
            className="flex-1"
          >
            Eliminar cuenta
          </Button>
        </div>
      </div>
    </Modal>
  );
};