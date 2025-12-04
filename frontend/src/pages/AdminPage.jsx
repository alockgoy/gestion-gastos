import { useState, useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { adminAPI, tagsAPI } from '../services/api';
import { Card } from '../components/common/Card';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { Button } from '../components/common/Button';
import { Input } from '../components/common/Input';
import { Select } from '../components/common/Select';
import { Modal } from '../components/common/Modal';
import { ConfirmDialog } from '../components/common/ConfirmDialog';
import { Badge } from '../components/common/Badge';
import {
  Users,
  Shield,
  Tag,
  Activity,
  Edit,
  Trash2,
  Plus,
  Search,
  BarChart3,
} from 'lucide-react';
import { formatDate } from '../utils/formatters';
import toast from 'react-hot-toast';

export const AdminPage = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('users');
  const isOwner = user?.rol === 'propietario';

  const tabs = [
    { id: 'users', name: 'Usuarios', icon: Users },
    { id: 'tags', name: 'Etiquetas', icon: Tag, ownerOnly: true },
    { id: 'activity', name: 'Historial', icon: Activity, ownerOnly: true },
    { id: 'stats', name: 'Estadísticas', icon: BarChart3, ownerOnly: true },
  ];

  const filteredTabs = tabs.filter((tab) => !tab.ownerOnly || isOwner);

  return (
    <div className="space-y-6 fade-in">
      <div className="flex items-center gap-3">
        <div className="w-12 h-12 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center">
          <Shield className="w-6 h-6 text-white" />
        </div>
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Panel de Administración</h1>
          <p className="text-gray-600">Gestión avanzada del sistema</p>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="flex gap-8 overflow-x-auto">
          {filteredTabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors ${
                activeTab === tab.id
                  ? 'border-purple-500 text-purple-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              }`}
            >
              <tab.icon size={20} />
              {tab.name}
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      {activeTab === 'users' && <UsersTab isOwner={isOwner} currentUser={user} />}
      {activeTab === 'tags' && isOwner && <TagsTab />}
      {activeTab === 'activity' && isOwner && <ActivityTab />}
      {activeTab === 'stats' && isOwner && <StatsTab />}
    </div>
  );
};

// ============================================
// Users Tab
// ============================================
const UsersTab = ({ isOwner, currentUser }) => {
  const [loading, setLoading] = useState(true);
  const [users, setUsers] = useState([]);
  const [search, setSearch] = useState('');
  const [filterRole, setFilterRole] = useState('');
  const [selectedUser, setSelectedUser] = useState(null);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);

  useEffect(() => {
    loadUsers();
  }, [filterRole]);

  const loadUsers = async () => {
    try {
      const params = {};
      if (filterRole) params.rol = filterRole;
      if (search) params.search = search;
      
      const response = await adminAPI.getUsers(params);
      setUsers(response.data.data.users);
    } catch (error) {
      toast.error('Error al cargar usuarios');
    } finally {
      setLoading(false);
    }
  };

  const handleRoleChange = async (userId, newRole) => {
    try {
      await adminAPI.changeUserRole({ user_id: userId, new_role: newRole });
      toast.success('Rol actualizado');
      loadUsers();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al cambiar rol';
      toast.error(message);
    }
  };

  const handleDelete = async () => {
    try {
      await adminAPI.deleteUser({ user_id: selectedUser.id });
      toast.success('Usuario eliminado');
      loadUsers();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al eliminar';
      toast.error(message);
    }
  };

  const filteredUsers = users.filter((u) =>
    u.nombre_usuario.toLowerCase().includes(search.toLowerCase()) ||
    u.correo_electronico.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) {
    return <LoadingSpinner size="lg" />;
  }

  return (
    <div className="space-y-6">
      {/* Filters */}
      <Card>
        <div className="flex flex-col md:flex-row gap-4">
          <div className="flex-1">
            <Input
              placeholder="Buscar por nombre o correo..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              icon={<Search size={20} />}
            />
          </div>
          <Select
            value={filterRole}
            onChange={(e) => setFilterRole(e.target.value)}
            options={[
              { value: '', label: 'Todos los roles' },
              { value: 'usuario', label: 'Usuario' },
              { value: 'solicita', label: 'Solicita admin' },
              { value: 'administrador', label: 'Administrador' },
              { value: 'propietario', label: 'Propietario' },
            ]}
          />
        </div>
      </Card>

      {/* Users Table */}
      <Card className="overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Usuario
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Correo
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Rol
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  2FA
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  Último acceso
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredUsers.map((u) => (
                <tr key={u.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                        <Users size={20} className="text-primary-600" />
                      </div>
                      <span className="font-medium text-gray-900">
                        {u.nombre_usuario}
                      </span>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {u.correo_electronico}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {u.rol === 'propietario' ? (
                      <Badge variant="primary">{u.rol}</Badge>
                    ) : (
                      <Select
                        value={u.rol}
                        onChange={(e) => handleRoleChange(u.id, e.target.value)}
                        options={[
                          { value: 'usuario', label: 'Usuario' },
                          { value: 'solicita', label: 'Solicita' },
                          ...(isOwner ? [{ value: 'administrador', label: 'Admin' }] : []),
                        ]}
                        className="text-sm"
                      />
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <Badge variant={u.autenticacion_2fa ? 'success' : 'warning'}>
                      {u.autenticacion_2fa ? 'Sí' : 'No'}
                    </Badge>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {u.ultimo_logueo ? formatDate(u.ultimo_logueo) : 'Nunca'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right">
                    {u.rol !== 'propietario' && u.id !== currentUser?.id && (
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => {
                            setSelectedUser(u);
                            setIsEditModalOpen(true);
                          }}
                          className="text-primary-600 hover:text-primary-900"
                        >
                          <Edit size={18} />
                        </button>
                        <button
                          onClick={() => {
                            setSelectedUser(u);
                            setIsDeleteDialogOpen(true);
                          }}
                          className="text-red-600 hover:text-red-900"
                        >
                          <Trash2 size={18} />
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>

      {/* Edit Modal */}
      <EditUserModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false);
          setSelectedUser(null);
        }}
        user={selectedUser}
        onSuccess={() => {
          setIsEditModalOpen(false);
          setSelectedUser(null);
          loadUsers();
        }}
      />

      {/* Delete Dialog */}
      <ConfirmDialog
        isOpen={isDeleteDialogOpen}
        onClose={() => {
          setIsDeleteDialogOpen(false);
          setSelectedUser(null);
        }}
        onConfirm={handleDelete}
        title="Eliminar usuario"
        message={`¿Eliminar al usuario "${selectedUser?.nombre_usuario}"? Se eliminarán todos sus datos.`}
        confirmText="Eliminar"
        variant="danger"
      />
    </div>
  );
};

// Edit User Modal
const EditUserModal = ({ isOpen, onClose, user, onSuccess }) => {
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    nombre_usuario: '',
    correo_electronico: '',
    autenticacion_2fa: false,
  });

  useEffect(() => {
    if (user) {
      setFormData({
        nombre_usuario: user.nombre_usuario,
        correo_electronico: user.correo_electronico,
        autenticacion_2fa: user.autenticacion_2fa,
      });
    }
  }, [user]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      await adminAPI.updateUser({
        user_id: user.id,
        ...formData,
      });
      toast.success('Usuario actualizado');
      onSuccess();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al actualizar';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Editar Usuario">
      <form onSubmit={handleSubmit} className="space-y-4">
        <Input
          label="Nombre de usuario"
          value={formData.nombre_usuario}
          onChange={(e) =>
            setFormData({ ...formData, nombre_usuario: e.target.value })
          }
          required
        />
        <Input
          label="Correo electrónico"
          type="email"
          value={formData.correo_electronico}
          onChange={(e) =>
            setFormData({ ...formData, correo_electronico: e.target.value })
          }
          required
        />
        <div className="flex items-center gap-3">
          <input
            type="checkbox"
            id="2fa"
            checked={formData.autenticacion_2fa}
            onChange={(e) =>
              setFormData({ ...formData, autenticacion_2fa: e.target.checked })
            }
            className="w-4 h-4 text-primary-600 rounded"
          />
          <label htmlFor="2fa" className="text-sm text-gray-700">
            Verificación en 2 pasos
          </label>
        </div>
        <div className="flex gap-3 pt-4">
          <Button variant="secondary" onClick={onClose} className="flex-1">
            Cancelar
          </Button>
          <Button type="submit" loading={loading} className="flex-1">
            Guardar
          </Button>
        </div>
      </form>
    </Modal>
  );
};

// ============================================
// Tags Tab
// ============================================
const TagsTab = () => {
  const [loading, setLoading] = useState(true);
  const [tags, setTags] = useState([]);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [selectedTag, setSelectedTag] = useState(null);

  useEffect(() => {
    loadTags();
  }, []);

  const loadTags = async () => {
    try {
      const response = await tagsAPI.getAll();
      setTags(response.data.data.etiquetas);
    } catch (error) {
      toast.error('Error al cargar etiquetas');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    try {
      await adminAPI.deleteTag(selectedTag.id);
      toast.success('Etiqueta eliminada');
      loadTags();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al eliminar';
      toast.error(message);
    }
  };

  if (loading) {
    return <LoadingSpinner size="lg" />;
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <p className="text-gray-600">
          Gestiona las etiquetas disponibles para las cuentas
        </p>
        <Button onClick={() => setIsCreateModalOpen(true)}>
          <Plus size={20} />
          Nueva Etiqueta
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {tags.map((tag) => (
          <Card key={tag.id} hover>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                  <Tag className="w-5 h-5 text-purple-600" />
                </div>
                <div>
                  <p className="font-medium text-gray-900">{tag.nombre}</p>
                  <p className="text-sm text-gray-600">
                    {tag.cuentas_usando} cuenta(s)
                  </p>
                </div>
              </div>
              <div className="flex gap-2">
                <button
                  onClick={() => {
                    setSelectedTag(tag);
                    setIsEditModalOpen(true);
                  }}
                  className="p-2 text-primary-600 hover:bg-primary-50 rounded-lg"
                  disabled={tag.cuentas_usando > 0}
                >
                  <Edit size={18} />
                </button>
                <button
                  onClick={() => {
                    setSelectedTag(tag);
                    setIsDeleteDialogOpen(true);
                  }}
                  className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                  disabled={tag.cuentas_usando > 0}
                >
                  <Trash2 size={18} />
                </button>
              </div>
            </div>
          </Card>
        ))}
      </div>

      <TagModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onSuccess={() => {
          setIsCreateModalOpen(false);
          loadTags();
        }}
      />

      <TagModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false);
          setSelectedTag(null);
        }}
        onSuccess={() => {
          setIsEditModalOpen(false);
          setSelectedTag(null);
          loadTags();
        }}
        tag={selectedTag}
      />

      <ConfirmDialog
        isOpen={isDeleteDialogOpen}
        onClose={() => {
          setIsDeleteDialogOpen(false);
          setSelectedTag(null);
        }}
        onConfirm={handleDelete}
        title="Eliminar etiqueta"
        message={`¿Eliminar la etiqueta "${selectedTag?.nombre}"?`}
        confirmText="Eliminar"
        variant="danger"
      />
    </div>
  );
};

const TagModal = ({ isOpen, onClose, onSuccess, tag = null }) => {
  const [loading, setLoading] = useState(false);
  const [nombre, setNombre] = useState('');

  useEffect(() => {
    setNombre(tag?.nombre || '');
  }, [tag]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      if (tag) {
        await adminAPI.updateTag(tag.id, { nombre });
        toast.success('Etiqueta actualizada');
      } else {
        await adminAPI.createTag({ nombre });
        toast.success('Etiqueta creada');
      }
      onSuccess();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al guardar';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={tag ? 'Editar Etiqueta' : 'Nueva Etiqueta'}
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <Input
          label="Nombre"
          value={nombre}
          onChange={(e) => setNombre(e.target.value)}
          maxLength="100"
          required
        />
        <div className="flex gap-3">
          <Button variant="secondary" onClick={onClose} className="flex-1">
            Cancelar
          </Button>
          <Button type="submit" loading={loading} className="flex-1">
            {tag ? 'Actualizar' : 'Crear'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};

// ============================================
// Activity Tab
// ============================================
const ActivityTab = () => {
  const [loading, setLoading] = useState(true);
  const [logs, setLogs] = useState([]);
  const [pagination, setPagination] = useState({ page: 1, limit: 50, total: 0 });

  useEffect(() => {
    loadLogs();
  }, [pagination.page]);

  const loadLogs = async () => {
    try {
      const response = await adminAPI.getActivityLog({
        limit: pagination.limit,
        offset: (pagination.page - 1) * pagination.limit,
      });
      setLogs(response.data.data.logs);
      setPagination((prev) => ({
        ...prev,
        total: response.data.data.total,
      }));
    } catch (error) {
      toast.error('Error al cargar historial');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <LoadingSpinner size="lg" />;
  }

  return (
    <div className="space-y-6">
      <Card>
        <div className="space-y-4">
          {logs.map((log) => (
            <div
              key={log.id}
              className="flex items-start gap-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              <div className="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Activity size={20} className="text-purple-600" />
              </div>
              <div className="flex-1">
                <p className="text-sm text-gray-900">
                  <span className="font-medium">{log.nombre_usuario}</span> {log.accion}
                </p>
                <div className="flex items-center gap-4 mt-1 text-xs text-gray-500">
                  <span>{formatDate(log.fecha)}</span>
                  <span>IP: {log.ip}</span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
};

// ============================================
// Stats Tab
// ============================================
const StatsTab = () => {
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      const response = await adminAPI.getStats();
      setStats(response.data.data.stats);
    } catch (error) {
      toast.error('Error al cargar estadísticas');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <LoadingSpinner size="lg" />;
  }

  const statCards = [
    { label: 'Total Usuarios', value: stats.total_usuarios, color: 'blue' },
    { label: 'Usuarios Activos (30 días)', value: stats.usuarios_activos, color: 'green' },
    { label: 'Total Cuentas', value: stats.total_cuentas, color: 'purple' },
    { label: 'Total Movimientos', value: stats.total_movimientos, color: 'orange' },
    { label: 'Usuarios con 2FA', value: stats.usuarios_con_2fa, color: 'pink' },
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {statCards.map((stat, index) => (
        <Card key={index}>
          <p className="text-sm text-gray-600 mb-1">{stat.label}</p>
          <p className="text-3xl font-bold text-gray-900">{stat.value}</p>
        </Card>
      ))}
    </div>
  );
};