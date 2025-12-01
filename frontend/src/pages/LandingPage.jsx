import { Link } from 'react-router-dom';
import { 
  Wallet, 
  TrendingUp, 
  Shield, 
  Smartphone,
  Github,
  ChevronRight,
  Check
} from 'lucide-react';

export const LandingPage = () => {
  const features = [
    {
      icon: Wallet,
      title: 'Gestión de Cuentas',
      description: 'Administra múltiples cuentas bancarias y de efectivo en un solo lugar.',
    },
    {
      icon: TrendingUp,
      title: 'Seguimiento de Movimientos',
      description: 'Registra ingresos y gastos con archivos adjuntos y notas detalladas.',
    },
    {
      icon: Shield,
      title: 'Seguridad Avanzada',
      description: 'Autenticación de dos factores y encriptación de contraseñas.',
    },
    {
      icon: Smartphone,
      title: 'Acceso desde Telegram',
      description: 'Gestiona tus finanzas directamente desde tu app de mensajería favorita.',
    },
  ];

  return (
    <div className="min-h-screen bg-white">
      {/* Header */}
      <header className="border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className="w-10 h-10 bg-gradient-to-br from-primary-600 to-secondary-600 rounded-xl flex items-center justify-center">
                <span className="text-white font-bold text-xl">G</span>
              </div>
              <span className="font-bold text-xl text-gray-900">Gestión de Gastos</span>
            </div>
            <div className="flex items-center gap-3">
              <Link to="/login">
                <button className="btn-secondary">
                  Iniciar Sesión
                </button>
              </Link>
              <Link to="/register">
                <button className="btn-primary">
                  Registrarse
                </button>
              </Link>
            </div>
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="py-20 px-4">
        <div className="max-w-7xl mx-auto text-center">
          <h1 className="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
            Controla tus finanzas
            <span className="block text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-secondary-600">
              de forma inteligente
            </span>
          </h1>
          <p className="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
            Una aplicación web completa para gestionar tus ingresos y gastos personales.
            Simple, segura y potente.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/register">
              <button className="btn-primary text-lg px-8 py-3 inline-flex items-center gap-2">
                Comenzar gratis
                <ChevronRight size={20} />
              </button>
            </Link>
            <a
              href="https://github.com"
              target="_blank"
              rel="noopener noreferrer"
              className="btn-secondary text-lg px-8 py-3 inline-flex items-center gap-2"
            >
              <Github size={20} />
              Ver en GitHub
            </a>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="py-20 bg-gray-50 px-4">
        <div className="max-w-7xl mx-auto">
          <h2 className="text-3xl font-bold text-center text-gray-900 mb-12">
            Todo lo que necesitas
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {features.map((feature, index) => (
              <div
                key={index}
                className="bg-white p-6 rounded-xl shadow-soft hover:shadow-lg transition-shadow"
              >
                <div className="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                  <feature.icon className="w-6 h-6 text-primary-600" />
                </div>
                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                  {feature.title}
                </h3>
                <p className="text-gray-600">
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features List */}
      <section className="py-20 px-4">
        <div className="max-w-4xl mx-auto">
          <h2 className="text-3xl font-bold text-center text-gray-900 mb-12">
            Características principales
          </h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {[
              'Balance automático con triggers SQL',
              'Exportación e importación de datos (CSV/JSON)',
              'Metas de ahorro personalizadas',
              'Archivos adjuntos (PDF, imágenes)',
              'Sistema de roles (usuario, admin, propietario)',
              'Historial completo de acciones',
              'Verificación en 2 pasos',
              'Bot de Telegram integrado',
              'Diseño responsive',
              'Panel de administración completo',
            ].map((feature, index) => (
              <div key={index} className="flex items-center gap-3">
                <div className="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                  <Check className="w-4 h-4 text-green-600" />
                </div>
                <span className="text-gray-700">{feature}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20 bg-gradient-to-br from-primary-600 to-secondary-600 px-4">
        <div className="max-w-4xl mx-auto text-center">
          <h2 className="text-4xl font-bold text-white mb-6">
            ¿Listo para tomar el control?
          </h2>
          <p className="text-xl text-primary-100 mb-8">
            Únete a cientos de usuarios que ya están gestionando sus finanzas de forma inteligente.
          </p>
          <Link to="/register">
            <button className="bg-white text-primary-600 hover:bg-gray-100 px-8 py-3 rounded-lg font-semibold text-lg transition-colors">
              Crear cuenta gratuita
            </button>
          </Link>
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-gray-200 py-8 px-4">
        <div className="max-w-7xl mx-auto text-center text-gray-600">
          <p>© 2025 Gestión de Gastos. Proyecto open source. Creado con Claude AI 4.5</p>
          <div className="mt-4">
            <a
              href="https://github.com"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700"
            >
              <Github size={20} />
              Ver código fuente
            </a>
          </div>
        </div>
      </footer>
    </div>
  );
};