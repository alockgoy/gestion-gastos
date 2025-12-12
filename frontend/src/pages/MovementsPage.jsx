import { useState, useEffect, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';
import { movementsAPI, accountsAPI } from '../services/api';
import { Card } from '../components/common/Card';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { Button } from '../components/common/Button';
import { Modal } from '../components/common/Modal';
import { Input } from '../components/common/Input';
import { Select } from '../components/common/Select';
import { ConfirmDialog } from '../components/common/ConfirmDialog';
import { EmptyState } from '../components/common/EmptyState';
import { Badge } from '../components/common/Badge';
import { MovementModal, ImportModal } from '../components/movements/MovementModals';

import {
  Plus,
  TrendingUp,
  TrendingDown,
  Filter,
  Download,
  Upload,
  X,
  Paperclip,
  Eye,
  Edit,
  Trash2,
  Calendar,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';
import { formatMoney, formatDate } from '../utils/formatters';
import toast from 'react-hot-toast';

export const MovementsPage = () => {
  const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8080';
  const [searchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [movements, setMovements] = useState([]);
  const [accounts, setAccounts] = useState([]);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [isImportModalOpen, setIsImportModalOpen] = useState(false);
  const [selectedMovement, setSelectedMovement] = useState(null);
  const [showFilters, setShowFilters] = useState(false);
  const [filters, setFilters] = useState({
    id_cuenta: '',
    tipo: '',
    fecha_desde: '',
    fecha_hasta: '',
    order_by: 'fecha_movimiento',
    order_dir: 'DESC',
  });
  const [pagination, setPagination] = useState({
    page: 1,
    limit: 20,
    total: 0,
  });
  const isInitialLoadRef = useRef(true);

  useEffect(() => {
    loadData();
    const action = searchParams.get('action');
    const type = searchParams.get('type');
    if (action === 'create') {
      setIsCreateModalOpen(true);
    }
    isInitialLoadRef.current = false;
  }, [searchParams]);

  useEffect(() => {
    if (!isInitialLoadRef.current) {
      loadMovements();
    }
  }, [filters, pagination.page]);

  const loadData = async () => {
    try {
      const [movementsRes, accountsRes] = await Promise.all([
        movementsAPI.getAll({
          limit: pagination.limit,
          offset: 0,
        }),
        accountsAPI.getAll(),
      ]);
      setMovements(movementsRes.data.data.movimientos);
      setPagination((prev) => ({
        ...prev,
        total: movementsRes.data.data.total,
      }));
      setAccounts(accountsRes.data.data.cuentas);
    } catch (error) {
      toast.error('Error al cargar datos');
    } finally {
      setLoading(false);
    }
  };

  const loadMovements = async () => {
    try {
      const cleanFilters = Object.fromEntries(
        Object.entries(filters).filter(([_, v]) => v !== '')
      );
      const response = await movementsAPI.getAll({
        ...cleanFilters,
        limit: pagination.limit,
        offset: (pagination.page - 1) * pagination.limit,
      });
      setMovements(response.data.data.movimientos);
      setPagination((prev) => ({
        ...prev,
        total: response.data.data.total,
      }));
    } catch (error) {
      toast.error('Error al cargar movimientos');
    }
  };

  const handleDelete = async () => {
    try {
      await movementsAPI.delete(selectedMovement.id);
      toast.success('Movimiento eliminado');
      loadMovements();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al eliminar';
      toast.error(message);
    }
  };

  const handleExportCSV = async () => {
    try {
      // Filtrar solo los parámetros relevantes para la exportación (sin paginación)
      const exportFilters = {
        id_cuenta: filters.id_cuenta,
        tipo: filters.tipo,
        fecha_desde: filters.fecha_desde,
        fecha_hasta: filters.fecha_hasta,
      };

      // Remover campos vacíos
      Object.keys(exportFilters).forEach(key => {
        if (!exportFilters[key]) delete exportFilters[key];
      });

      const response = await movementsAPI.exportCSV(exportFilters);
      const blob = new Blob([response.data], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `movimientos_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      toast.success('Exportado a CSV');
    } catch (error) {
      toast.error('Error al exportar');
    }
  };

  const handleExportJSON = async () => {
    try {
      // Filtrar solo los parámetros relevantes para la exportación (sin paginación)
      const exportFilters = {
        id_cuenta: filters.id_cuenta,
        tipo: filters.tipo,
        fecha_desde: filters.fecha_desde,
        fecha_hasta: filters.fecha_hasta,
      };

      // Remover campos vacíos
      Object.keys(exportFilters).forEach(key => {
        if (!exportFilters[key]) delete exportFilters[key];
      });

      const response = await movementsAPI.exportJSON(exportFilters);
      const blob = new Blob([response.data], {
        type: 'application/json',
      });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `movimientos_${new Date().toISOString().split('T')[0]}.json`;
      a.click();
      toast.success('Exportado a JSON');
    } catch (error) {
      toast.error('Error al exportar');
    }
  };

  const resetFilters = () => {
    setFilters({
      id_cuenta: '',
      tipo: '',
      fecha_desde: '',
      fecha_hasta: '',
      order_by: 'fecha_movimiento',
      order_dir: 'DESC',
    });
    setPagination((prev) => ({ ...prev, page: 1 }));
  };

  const totalPages = Math.ceil(pagination.total / pagination.limit);

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
          <h1 className="text-3xl font-bold text-gray-900">Movimientos</h1>
          <p className="text-gray-600 mt-1">
            Historial completo de ingresos y gastos
          </p>
        </div>
        <div className="flex flex-wrap gap-3">
          <Button variant="secondary" onClick={() => setShowFilters(!showFilters)}>
            <Filter size={20} />
            Filtros
          </Button>
          <Button variant="secondary" onClick={handleExportJSON}>
            <Download size={20} />
            Exportar JSON
          </Button>
          <Button variant="secondary" onClick={() => setIsImportModalOpen(true)}>
            <Upload size={20} />
            Importar
          </Button>
          <Button onClick={() => setIsCreateModalOpen(true)}>
            <Plus size={20} />
            Nuevo
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
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Select
              label="Cuenta"
              value={filters.id_cuenta}
              onChange={(e) =>
                setFilters({ ...filters, id_cuenta: e.target.value })
              }
              options={[
                { value: '', label: 'Todas las cuentas' },
                ...accounts.map((acc) => ({ value: acc.id, label: acc.nombre })),
              ]}
            />
            <Select
              label="Tipo"
              value={filters.tipo}
              onChange={(e) => setFilters({ ...filters, tipo: e.target.value })}
              options={[
                { value: '', label: 'Todos' },
                { value: 'ingreso', label: 'Ingresos' },
                { value: 'retirada', label: 'Gastos' },
              ]}
            />
            <Input
              label="Desde"
              type="date"
              value={filters.fecha_desde}
              onChange={(e) =>
                setFilters({ ...filters, fecha_desde: e.target.value })
              }
            />
            <Input
              label="Hasta"
              type="date"
              value={filters.fecha_hasta}
              onChange={(e) =>
                setFilters({ ...filters, fecha_hasta: e.target.value })
              }
            />
            <Select
              label="Ordenar por"
              value={filters.order_by}
              onChange={(e) =>
                setFilters({ ...filters, order_by: e.target.value })
              }
              options={[
                { value: 'fecha_movimiento', label: 'Fecha' },
                { value: 'cantidad', label: 'Cantidad' },
              ]}
            />
            <Select
              label="Dirección"
              value={filters.order_dir}
              onChange={(e) =>
                setFilters({ ...filters, order_dir: e.target.value })
              }
              options={[
                { value: 'DESC', label: 'Descendente' },
                { value: 'ASC', label: 'Ascendente' },
              ]}
            />
            <div className="lg:col-span-2 flex items-end">
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

      {/* Movements Table */}
      {movements.length === 0 ? (
        <EmptyState
          icon={TrendingUp}
          title="No hay movimientos"
          description="Registra tu primer ingreso o gasto para comenzar"
          action={
            <Button onClick={() => setIsCreateModalOpen(true)}>
              <Plus size={20} />
              Crear movimiento
            </Button>
          }
        />
      ) : (
        <>
          <Card className="overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50 border-b border-gray-200">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Fecha
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tipo
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Cuenta
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Cantidad
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Notas
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Acciones
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {movements.map((movement) => (
                    <tr key={movement.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <div className="flex items-center gap-2">
                          <Calendar size={16} />
                          {formatDate(movement.fecha_movimiento)}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {movement.tipo === 'ingreso' ? (
                          <Badge variant="success">
                            <TrendingUp size={14} className="mr-1" />
                            Ingreso
                          </Badge>
                        ) : (
                          <Badge variant="danger">
                            <TrendingDown size={14} className="mr-1" />
                            Gasto
                          </Badge>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center gap-2">
                          <div
                            className="w-3 h-3 rounded-full"
                            style={{ backgroundColor: movement.cuenta_color }}
                          ></div>
                          <span className="text-sm font-medium text-gray-900">
                            {movement.cuenta_nombre}
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span
                          className={`text-sm font-semibold ${movement.tipo === 'ingreso'
                            ? 'text-green-600'
                            : 'text-red-600'
                            }`}
                        >
                          {movement.tipo === 'ingreso' ? '+' : '-'}
                          {formatMoney(movement.cantidad)}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        <div className="max-w-xs truncate">
                          {movement.notas || '-'}
                        </div>
                        {movement.adjunto && (
                          <a
                            href={`${BACKEND_URL}/uploads/movements/${movement.adjunto}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-1 text-primary-600 hover:text-primary-800 mt-1 cursor-pointer"
                          >
                            <Paperclip size={14} />
                            <span className="text-xs underline">Ver archivo adjunto</span>
                          </a>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex items-center justify-end gap-2">
                          <button
                            onClick={() => {
                              setSelectedMovement(movement);
                              setIsEditModalOpen(true);
                            }}
                            className="text-primary-600 hover:text-primary-900"
                          >
                            <Edit size={18} />
                          </button>
                          <button
                            onClick={() => {
                              setSelectedMovement(movement);
                              setIsDeleteDialogOpen(true);
                            }}
                            className="text-red-600 hover:text-red-900"
                          >
                            <Trash2 size={18} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-between">
              <p className="text-sm text-gray-600">
                Mostrando {(pagination.page - 1) * pagination.limit + 1} -{' '}
                {Math.min(pagination.page * pagination.limit, pagination.total)} de{' '}
                {pagination.total} movimientos
              </p>
              <div className="flex items-center gap-2">
                <Button
                  variant="secondary"
                  onClick={() =>
                    setPagination((prev) => ({ ...prev, page: prev.page - 1 }))
                  }
                  disabled={pagination.page === 1}
                >
                  <ChevronLeft size={20} />
                </Button>
                <span className="text-sm text-gray-600">
                  Página {pagination.page} de {totalPages}
                </span>
                <Button
                  variant="secondary"
                  onClick={() =>
                    setPagination((prev) => ({ ...prev, page: prev.page + 1 }))
                  }
                  disabled={pagination.page === totalPages}
                >
                  <ChevronRight size={20} />
                </Button>
              </div>
            </div>
          )}
        </>
      )}

      {/* Modals */}
      <MovementModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        onSuccess={() => {
          setIsCreateModalOpen(false);
          loadMovements();
        }}
        accounts={accounts}
      />

      <MovementModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false);
          setSelectedMovement(null);
        }}
        onSuccess={() => {
          setIsEditModalOpen(false);
          setSelectedMovement(null);
          loadMovements();
        }}
        accounts={accounts}
        movement={selectedMovement}
      />

      <ImportModal
        isOpen={isImportModalOpen}
        onClose={() => setIsImportModalOpen(false)}
        onSuccess={() => {
          setIsImportModalOpen(false);
          loadMovements();
        }}
      />

      <ConfirmDialog
        isOpen={isDeleteDialogOpen}
        onClose={() => {
          setIsDeleteDialogOpen(false);
          setSelectedMovement(null);
        }}
        onConfirm={handleDelete}
        title="Eliminar movimiento"
        message="¿Estás seguro de eliminar este movimiento? Se actualizará el balance de la cuenta."
        confirmText="Eliminar"
        variant="danger"
      />
    </div>
  );
};