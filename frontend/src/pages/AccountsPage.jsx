import { useState, useEffect, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import { accountsAPI, tagsAPI } from '../services/api';
import { Card } from '../components/common/Card';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { Button } from '../components/common/Button';
import { Modal } from '../components/common/Modal';
import { Input } from '../components/common/Input';
import { Select } from '../components/common/Select';
import { ConfirmDialog } from '../components/common/ConfirmDialog';
import { EmptyState } from '../components/common/EmptyState';
import { Badge } from '../components/common/Badge';
import {
  Plus,
  CreditCard,
  Edit,
  Trash2,
  Target,
  Filter,
  X
} from 'lucide-react';
import { formatMoney } from '../utils/formatters';
import toast from 'react-hot-toast';

export const AccountsPage = () => {
  const [searchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [accounts, setAccounts] = useState([]);
  const [tags, setTags] = useState([]);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [selectedAccount, setSelectedAccount] = useState(null);
  const [filters, setFilters] = useState({ tipo: '', id_etiqueta: '' });
  const [showFilters, setShowFilters] = useState(false);
  const isInitialLoadRef = useRef(true);

  useEffect(() => {
    loadData();
    if (searchParams.get('action') === 'create') {
      setIsCreateModalOpen(true);
    }
    isInitialLoadRef.current = false;
  }, [searchParams]);

  useEffect(() => {
    if (!isInitialLoadRef.current) {
      loadAccounts();
    }
  }, [filters]);

  const loadData = async () => {
    try {
      const [accountsRes, tagsRes] = await Promise.all([
        accountsAPI.getAll(),
        tagsAPI.getAll(),
      ]);
      setAccounts(accountsRes.data.data.cuentas);
      setTags(tagsRes.data.data.etiquetas);
    } catch (error) {
      toast.error('Error al cargar datos');
    } finally {
      setLoading(false);
    }
  };

  const loadAccounts = async () => {
    try {
      const cleanFilters = Object.fromEntries(
        Object.entries(filters).filter(([_, v]) => v !== '')
      );
      const response = await accountsAPI.getAll(cleanFilters);
      setAccounts(response.data.data.cuentas);
    } catch (error) {
      toast.error('Error al cargar cuentas');
    }
  };

  const handleDelete = async () => {
    try {
      await accountsAPI.delete(selectedAccount.id);
      toast.success('Cuenta eliminada');
      loadAccounts();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al eliminar cuenta';
      toast.error(message);
    }
  };

  const resetFilters = () => {
    setFilters({ tipo: '', id_etiqueta: '' });
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  return (
    <div className="space-y-6 fade-in">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Cuentas</h1>
          <p className="text-gray-600 mt-1">
            Gestiona tus cuentas bancarias y de efectivo
          </p>
        </div>
        <div className="flex gap-3">
          <Button
            variant="secondary"
            onClick={() => setShowFilters(!showFilters)}
          >
            <Filter size={20} />
            Filtros
          </Button>
          <Button
            onClick={() => setIsCreateModalOpen(true)}
          >
            <Plus size={20} />
            Nueva Cuenta
          </Button>
        </div>
      </div>

      {/* Filters */}
      {showFilters && (
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h3 className="font-medium text-gray-900">Filtros</h3>
            <button
              onClick={() => setShowFilters(false)}
              className="text-gray-400 hover:text-gray-600"
            >
              <X size={20} />
            </button>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Select
              label="Tipo de cuenta"
              value={filters.tipo}
              onChange={(e) => setFilters({ ...filters, tipo: e.target.value })}
              options={[
                { value: '', label: 'Todos' },
                { value: 'efectivo', label: 'Efectivo' },
                { value: 'bancaria', label: 'Bancaria' },
              ]}
            />
            <Select
              label="Etiqueta"
              value={filters.id_etiqueta}
              onChange={(e) => setFilters({ ...filters, id_etiqueta: e.target.value })}
              options={[
                { value: '', label: 'Todas' },
                ...tags.map(tag => ({ value: tag.id, label: tag.nombre })),
              ]}
            />
            <div className="flex items-end">
              <Button
                variant="secondary"
                onClick={resetFilters}
                className="w-full"
              >
                Limpiar filtros
              </Button>
            </div>
          </div>
        </Card>
      )}

      {/* Accounts Grid */}
      {accounts.length === 0 ? (
        <EmptyState
          icon={CreditCard}
          title="No tienes cuentas"
          description="Crea tu primera cuenta para comenzar a gestionar tus finanzas"
          action={
            <Button onClick={() => setIsCreateModalOpen(true)}>
              <Plus size={20} />
              Crear primera cuenta
            </Button>
          }
        />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {accounts.map((account) => (
            <Card key={account.id} hover className="relative">
              {/* Account Header */}
              <div className="flex items-start justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div
                    className="w-12 h-12 rounded-xl flex items-center justify-center"
                    style={{ backgroundColor: `${account.color}20` }}
                  >
                    <CreditCard size={24} style={{ color: account.color }} />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900">{account.nombre}</h3>
                    <Badge variant="primary">{account.tipo}</Badge>
                  </div>
                </div>
                <div className="flex gap-2">
                  <button
                    onClick={() => {
                      setSelectedAccount(account);
                      setIsEditModalOpen(true);
                    }}
                    className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                  >
                    <Edit size={18} />
                  </button>
                  <button
                    onClick={() => {
                      setSelectedAccount(account);
                      setIsDeleteDialogOpen(true);
                    }}
                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  >
                    <Trash2 size={18} />
                  </button>
                </div>
              </div>

              {/* Balance */}
              <div className="mb-4">
                <p className="text-sm text-gray-600 mb-1">Balance actual</p>
                <p className="text-2xl font-bold text-gray-900">
                  {formatMoney(account.balance)}
                </p>
              </div>

              {/* Description */}
              {account.descripcion && (
                <p className="text-sm text-gray-600 mb-4">{account.descripcion}</p>
              )}

              {/* Tag */}
              {account.etiqueta_nombre && (
                <div className="flex items-center gap-2 mb-4">
                  <span className="text-sm text-gray-600">Etiqueta:</span>
                  <Badge variant="secondary">{account.etiqueta_nombre}</Badge>
                </div>
              )}

              {/* Goal Progress */}
              {account.progreso_meta && (
                <div className="pt-4 border-t border-gray-200">
                  <div className="flex items-center gap-2 mb-2">
                    <Target size={16} className="text-primary-600" />
                    <span className="text-sm font-medium text-gray-900">
                      Meta: {formatMoney(account.meta)}
                    </span>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
                    <div
                      className="bg-primary-600 h-2 rounded-full transition-all"
                      style={{ width: `${Math.min(account.progreso_meta.porcentaje, 100)}%` }}
                    ></div>
                  </div>
                  <p className="text-xs text-gray-600">
                    {account.progreso_meta.mensaje}
                  </p>
                </div>
              )}
            </Card>
          ))}
        </div>
      )}

      {/* Create Modal */}
      <AccountModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onSuccess={() => {
          setIsCreateModalOpen(false);
          loadAccounts();
        }}
        tags={tags}
      />

      {/* Edit Modal */}
      <AccountModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false);
          setSelectedAccount(null);
        }}
        onSuccess={() => {
          setIsEditModalOpen(false);
          setSelectedAccount(null);
          loadAccounts();
        }}
        tags={tags}
        account={selectedAccount}
      />

      {/* Delete Dialog */}
      <ConfirmDialog
        isOpen={isDeleteDialogOpen}
        onClose={() => {
          setIsDeleteDialogOpen(false);
          setSelectedAccount(null);
        }}
        onConfirm={handleDelete}
        title="Eliminar cuenta"
        message={`¿Estás seguro de eliminar la cuenta "${selectedAccount?.nombre}"? Esta acción no se puede deshacer.`}
        confirmText="Eliminar"
        variant="danger"
      />
    </div>
  );
};

// ============================================
// AccountModal Component
// ============================================
const AccountModal = ({ isOpen, onClose, onSuccess, tags, account = null }) => {
  const isEdit = !!account;
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    tipo: 'bancaria',
    nombre: '',
    descripcion: '',
    balance: '0',
    id_etiqueta: '',
    color: '#667eea',
    meta: '',
  });

  useEffect(() => {
    if (account) {
      setFormData({
        tipo: account.tipo,
        nombre: account.nombre,
        descripcion: account.descripcion || '',
        balance: account.balance,
        id_etiqueta: account.id_etiqueta || '',
        color: account.color,
        meta: account.meta || '',
      });
    } else {
      setFormData({
        tipo: 'bancaria',
        nombre: '',
        descripcion: '',
        balance: '0',
        id_etiqueta: '',
        color: '#667eea',
        meta: '',
      });
    }
  }, [account, isOpen]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const data = {
        ...formData,
        balance: parseFloat(formData.balance) || 0,
        meta: formData.meta ? parseFloat(formData.meta) : null,
        id_etiqueta: formData.id_etiqueta || null,
      };

      if (isEdit) {
        await accountsAPI.update(account.id, data);
        toast.success('Cuenta actualizada');
      } else {
        await accountsAPI.create(data);
        toast.success('Cuenta creada');
      }
      onSuccess();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al guardar cuenta';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  const colors = [
    '#667eea', '#764ba2', '#f093fb', '#f5576c',
    '#fa709a', '#fee140', '#30cfd0', '#a8edea',
    '#ffd89b', '#19547b'
  ];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEdit ? 'Editar Cuenta' : 'Nueva Cuenta'}
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <Select
          label="Tipo de cuenta"
          value={formData.tipo}
          onChange={(e) => setFormData({ ...formData, tipo: e.target.value })}
          options={[
            { value: 'bancaria', label: 'Bancaria' },
            { value: 'efectivo', label: 'Efectivo' },
          ]}
          required
        />

        <Input
          label="Nombre"
          type="text"
          value={formData.nombre}
          onChange={(e) => setFormData({ ...formData, nombre: e.target.value })}
          placeholder="Ej: Santander Principal"
          maxLength="100"
          required
        />

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Descripción
          </label>
          <textarea
            className="input"
            value={formData.descripcion}
            onChange={(e) => setFormData({ ...formData, descripcion: e.target.value })}
            placeholder="Descripción opcional"
            rows="3"
            maxLength="500"
          />
        </div>

        {!isEdit && (
          <Input
            label="Balance inicial"
            type="number"
            step="0.01"
            value={formData.balance}
            onChange={(e) => setFormData({ ...formData, balance: e.target.value })}
            placeholder="0.00"
            required
          />
        )}

        <Select
          label="Etiqueta"
          value={formData.id_etiqueta}
          onChange={(e) => setFormData({ ...formData, id_etiqueta: e.target.value })}
          options={[
            { value: '', label: 'Sin etiqueta' },
            ...tags.map(tag => ({ value: tag.id, label: tag.nombre })),
          ]}
        />

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Color
          </label>
          <div className="grid grid-cols-5 gap-2">
            {colors.map((color) => (
              <button
                key={color}
                type="button"
                onClick={() => setFormData({ ...formData, color })}
                className={`w-full h-10 rounded-lg border-2 transition-all ${formData.color === color ? 'border-gray-900 scale-110' : 'border-gray-200'
                  }`}
                style={{ backgroundColor: color }}
              />
            ))}
          </div>
        </div>

        <Input
          label="Meta de ahorro (opcional)"
          type="number"
          step="0.01"
          value={formData.meta}
          onChange={(e) => setFormData({ ...formData, meta: e.target.value })}
          placeholder="1000.00"
        />

        <div className="flex gap-3 pt-4">
          <Button
            type="button"
            variant="secondary"
            onClick={onClose}
            className="flex-1"
          >
            Cancelar
          </Button>
          <Button
            type="submit"
            loading={loading}
            className="flex-1"
          >
            {isEdit ? 'Actualizar' : 'Crear'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};