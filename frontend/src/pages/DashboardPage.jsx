import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { accountsAPI, movementsAPI } from '../services/api';
import { Card } from '../components/common/Card';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import { Badge } from '../components/common/Badge';
import { 
  Wallet, 
  TrendingUp, 
  TrendingDown, 
  CreditCard,
  Plus,
  ArrowRight,
  Euro
} from 'lucide-react';
import { formatMoney, formatDate } from '../utils/formatters';
import toast from 'react-hot-toast';

export const DashboardPage = () => {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [summary, setSummary] = useState(null);
  const [accounts, setAccounts] = useState([]);
  const [recentMovements, setRecentMovements] = useState([]);
  const [stats, setStats] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      const [summaryRes, accountsRes, movementsRes, statsRes] = await Promise.all([
        accountsAPI.getSummary(),
        accountsAPI.getAll(),
        movementsAPI.getAll({ limit: 10 }),
        movementsAPI.getStats(),
      ]);

      setSummary(summaryRes.data.data.summary);
      setAccounts(accountsRes.data.data.cuentas);
      setRecentMovements(movementsRes.data.data.movimientos);
      setStats(statsRes.data.data.stats);
    } catch (error) {
      toast.error('Error al cargar datos');
    } finally {
      setLoading(false);
    }
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
      <div>
        <h1 className="text-3xl font-bold text-gray-900">
          Bienvenido, {user?.nombre_usuario} 👋
        </h1>
        <p className="text-gray-600 mt-1">
          Aquí está el resumen de tus finanzas
        </p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card className="border-l-4 border-primary-500">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Balance Total</p>
              <p className="text-2xl font-bold text-gray-900 mt-1">
                {formatMoney(summary?.balance_total || 0)}
              </p>
            </div>
            <div className="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
              <Wallet className="w-6 h-6 text-primary-600" />
            </div>
          </div>
        </Card>

        <Card className="border-l-4 border-green-500">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Total Ingresos</p>
              <p className="text-2xl font-bold text-green-600 mt-1">
                {formatMoney(stats?.suma_ingresos || 0)}
              </p>
            </div>
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <TrendingUp className="w-6 h-6 text-green-600" />
            </div>
          </div>
        </Card>

        <Card className="border-l-4 border-red-500">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Total Gastos</p>
              <p className="text-2xl font-bold text-red-600 mt-1">
                {formatMoney(stats?.suma_retiradas || 0)}
              </p>
            </div>
            <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <TrendingDown className="w-6 h-6 text-red-600" />
            </div>
          </div>
        </Card>

        <Card className="border-l-4 border-blue-500">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Cuentas Activas</p>
              <p className="text-2xl font-bold text-gray-900 mt-1">
                {summary?.total_cuentas || 0}
              </p>
            </div>
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <CreditCard className="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </Card>
      </div>

      {/* Accounts and Recent Movements */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Accounts */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-semibold text-gray-900">Mis Cuentas</h2>
            <Link to="/accounts" className="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1">
              Ver todas
              <ArrowRight size={16} />
            </Link>
          </div>

          {accounts.length === 0 ? (
            <div className="text-center py-8">
              <CreditCard className="w-12 h-12 text-gray-400 mx-auto mb-3" />
              <p className="text-gray-600 mb-4">No tienes cuentas aún</p>
              <Link to="/accounts">
                <button className="btn-primary inline-flex items-center gap-2">
                  <Plus size={20} />
                  Crear cuenta
                </button>
              </Link>
            </div>
          ) : (
            <div className="space-y-3">
              {accounts.slice(0, 5).map((account) => (
                <div
                  key={account.id}
                  className="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:border-primary-300 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <div
                      className="w-10 h-10 rounded-lg flex items-center justify-center"
                      style={{ backgroundColor: `${account.color}20` }}
                    >
                      <CreditCard
                        size={20}
                        style={{ color: account.color }}
                      />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">{account.nombre}</p>
                      <p className="text-sm text-gray-600">{account.etiqueta_nombre || account.tipo}</p>
                    </div>
                  </div>
                  <p className="font-semibold text-gray-900">
                    {formatMoney(account.balance)}
                  </p>
                </div>
              ))}
            </div>
          )}
        </Card>

        {/* Recent Movements */}
        <Card>
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-xl font-semibold text-gray-900">Movimientos Recientes</h2>
            <Link to="/movements" className="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1">
              Ver todos
              <ArrowRight size={16} />
            </Link>
          </div>

          {recentMovements.length === 0 ? (
            <div className="text-center py-8">
              <TrendingUp className="w-12 h-12 text-gray-400 mx-auto mb-3" />
              <p className="text-gray-600 mb-4">No hay movimientos registrados</p>
              <Link to="/movements">
                <button className="btn-primary inline-flex items-center gap-2">
                  <Plus size={20} />
                  Registrar movimiento
                </button>
              </Link>
            </div>
          ) : (
            <div className="space-y-3">
              {recentMovements.slice(0, 5).map((movement) => (
                <div
                  key={movement.id}
                  className="flex items-center justify-between p-3 rounded-lg border border-gray-200"
                >
                  <div className="flex items-center gap-3">
                    <div className={`w-10 h-10 rounded-lg flex items-center justify-center ${
                      movement.tipo === 'ingreso' ? 'bg-green-100' : 'bg-red-100'
                    }`}>
                      {movement.tipo === 'ingreso' ? (
                        <TrendingUp className="w-5 h-5 text-green-600" />
                      ) : (
                        <TrendingDown className="w-5 h-5 text-red-600" />
                      )}
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">{movement.cuenta_nombre}</p>
                      <p className="text-sm text-gray-600">
                        {formatDate(movement.fecha_movimiento)}
                      </p>
                    </div>
                  </div>
                  <p className={`font-semibold ${
                    movement.tipo === 'ingreso' ? 'text-green-600' : 'text-red-600'
                  }`}>
                    {movement.tipo === 'ingreso' ? '+' : '-'}{formatMoney(movement.cantidad)}
                  </p>
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>

      {/* Quick Actions */}
      <Card>
        <h2 className="text-xl font-semibold text-gray-900 mb-4">Acciones Rápidas</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Link
            to="/accounts?action=create"
            className="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-primary-500 hover:bg-primary-50 transition-all"
          >
            <Plus className="w-6 h-6 text-gray-600" />
            <span className="font-medium text-gray-900">Nueva cuenta</span>
          </Link>
          
          <Link
            to="/movements?action=create"
            className="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-green-500 hover:bg-green-50 transition-all"
          >
            <TrendingUp className="w-6 h-6 text-gray-600" />
            <span className="font-medium text-gray-900">Registrar ingreso</span>
          </Link>
          
          <Link
            to="/movements?action=create&type=retirada"
            className="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-red-500 hover:bg-red-50 transition-all"
          >
            <TrendingDown className="w-6 h-6 text-gray-600" />
            <span className="font-medium text-gray-900">Registrar gasto</span>
          </Link>
          
          <Link
            to="/movements?action=export"
            className="flex items-center gap-3 p-4 rounded-lg border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 transition-all"
          >
            <ArrowRight className="w-6 h-6 text-gray-600" />
            <span className="font-medium text-gray-900">Exportar datos</span>
          </Link>
        </div>
      </Card>
    </div>
  );
};
