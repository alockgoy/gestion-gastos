# 💰 Gestión de Gastos

Aplicación web completa para la gestión de ingresos y gastos personales, con backend en PHP, frontend en React y bot de Telegram.

![Estado](https://img.shields.io/badge/estado-100%25%20funcional-brightgreen)
![Backend](https://img.shields.io/badge/backend-PHP%208.2-blue)
![Frontend](https://img.shields.io/badge/frontend-React%2018-cyan)
![Bot](https://img.shields.io/badge/bot-Telegram-blue)

## 🌟 Características Principales

### 💼 Gestión Financiera
- ✅ Múltiples cuentas (bancarias y efectivo)
- ✅ Registro de ingresos y gastos
- ✅ Balance automático con triggers SQL
- ✅ Metas de ahorro con seguimiento
- ✅ Archivos adjuntos (PDF, imágenes)
- ✅ Exportación e importación (CSV/JSON)

### 🔐 Seguridad
- ✅ Autenticación con tokens JWT
- ✅ Verificación en 2 pasos (2FA)
- ✅ Contraseñas encriptadas con bcrypt
- ✅ Protección CSRF
- ✅ Sanitización de inputs
- ✅ Roles de usuario (4 niveles)

### 👥 Administración
- ✅ Panel de administración completo
- ✅ Gestión de usuarios
- ✅ Historial de acciones (auditoría)
- ✅ Estadísticas del sistema
- ✅ Gestión de etiquetas

### 🤖 Bot de Telegram
- ✅ Inicio de sesión desde Telegram
- ✅ Consulta de balance y cuentas
- ✅ Crear movimientos con archivos
- ✅ Ver historial de movimientos
- ✅ Eliminar movimientos

## 🏗️ Arquitectura

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Frontend      │────▶│   Backend API   │◀────│  Telegram Bot   │
│   React + Vite  │     │   PHP + MySQL   │     │     Python      │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                       │
         └───────────────────────┘
              Docker Network
```

## 📦 Tecnologías

### Backend
- PHP 8.2
- MySQL 8.0
- Apache 2.4
- Composer
- PHPMailer

### Frontend
- React 18
- Vite
- Tailwind CSS
- React Router
- Axios

### Bot
- Python 3.11
- python-telegram-bot
- requests

### DevOps
- Docker
- Docker Compose
- Nginx

## 🚀 Instalación Rápida

### 1. Clonar repositorio

```bash
git clone https://github.com/tu-usuario/gestion-gastos.git
cd gestion-gastos
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
nano .env
```

Configurar:
- Credenciales SMTP (Gmail, etc.)
- Token del bot de Telegram
- URLs si es necesario

### 3. Levantar con Docker

```bash
docker-compose up -d
```

### 4. Acceder a la aplicación

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8080/api
- **phpMyAdmin**: http://localhost:8081

### 5. Instalación inicial

1. Ir a http://localhost:8080
2. Completar el formulario de instalación
3. Crear usuario propietario

## 📱 Configurar Bot de Telegram

1. Buscar **@BotFather** en Telegram
2. Crear bot: `/newbot`
3. Copiar el token
4. Añadir token en `.env`:
   ```
   TELEGRAM_BOT_TOKEN=tu_token_aqui
   ```
5. Reiniciar servicios: `docker-compose restart`
6. Buscar tu bot en Telegram y usar `/start`

## 📖 Documentación

### Estructura del Proyecto

```
gestion-gastos/
├── backend/           # API REST en PHP
├── frontend/          # Aplicación React
├── telegram_bot/      # Bot de Telegram
├── docker-compose.yml # Orquestación Docker
└── .env.example       # Variables de entorno
```

### Endpoints API Principales

```
POST   /api/auth/login              # Iniciar sesión
POST   /api/auth/register           # Registrarse
GET    /api/user/profile            # Ver perfil
GET    /api/accounts                # Listar cuentas
POST   /api/accounts                # Crear cuenta
GET    /api/movements               # Listar movimientos
POST   /api/movements               # Crear movimiento
GET    /api/movements/export/csv    # Exportar CSV
POST   /api/movements/import        # Importar JSON
```

Ver documentación completa en `/backend/README.md`

### Comandos del Bot

```
/login          # Iniciar sesión
/balance        # Ver balance
/cuentas        # Listar cuentas
/movimientos    # Ver últimos movimientos
/nuevo          # Crear movimiento
/eliminar [ID]  # Eliminar movimiento
/logout         # Cerrar sesión
```

Ver guía completa en `/telegram_bot/README.md`

## 🎯 Roles de Usuario

| Rol | Permisos |
|-----|----------|
| **Propietario** | Control total del sistema, no eliminable |
| **Administrador** | Gestión de usuarios (excepto propietario) |
| **Usuario** | Gestión de sus propias cuentas y movimientos |
| **Solicita** | Usuario que solicitó ser administrador |

## 🔧 Desarrollo

### Backend

```bash
cd backend
composer install
# Configurar config.php si es necesario
```

### Frontend

```bash
cd frontend
npm install
npm run dev    # Desarrollo
npm run build  # Producción
```

### Bot

```bash
cd telegram_bot
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
python bot.py
```

## 🧪 Testing

```bash
# Backend (PHPUnit)
cd backend
composer test

# Frontend (Vitest)
cd frontend
npm test
```

## 📊 Características Destacadas

### Balance Automático
Los triggers SQL actualizan el balance automáticamente al crear, editar o eliminar movimientos.

### Metas de Ahorro
Configura objetivos de ahorro y ve tu progreso en tiempo real.

### Historial Completo
Auditoría de todas las acciones realizadas en el sistema (solo propietario).

### Exportación/Importación
Exporta tus datos en CSV o JSON y migra entre cuentas fácilmente.

### Archivos Adjuntos
Adjunta recibos, facturas o comprobantes (PDF, imágenes hasta 5MB).

## 🔒 Seguridad

- Contraseñas hasheadas con bcrypt (cost 12)
- Tokens seguros con `random_bytes()`
- Prepared statements (prevención SQL Injection)
- Validación y sanitización de inputs
- Headers de seguridad configurados
- CORS configurado
- Rate limiting preparado

## 📝 Tareas Programadas

El sistema incluye un cron job para:
- Enviar recordatorios a usuarios inactivos
- Eliminar cuentas inactivas (>2 años)
- Limpiar sesiones expiradas
- Limpiar tokens expirados

```bash
# Ejecutar manualmente
docker exec gastos_backend php /var/www/html/scripts/cron.php
```

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama: `git checkout -b feature/nueva-funcionalidad`
3. Commit: `git commit -m 'Añadir nueva funcionalidad'`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Crear Pull Request

## 📄 Licencia

Este proyecto es de código abierto bajo licencia MIT.

## 👨‍💻 Autor

Desarrollado como proyecto educativo full-stack.

## 🐛 Reportar Problemas

Crear un issue en GitHub con:
- Descripción del problema
- Pasos para reproducir
- Logs relevantes
- Screenshots si aplica

## 📞 Contacto

- GitHub: [tu-usuario](https://github.com/tu-usuario)
- Email: tu@email.com

---

⭐ Si te gusta el proyecto, dale una estrella en GitHub!

## 📸 Screenshots

### Dashboard
![Dashboard](docs/screenshots/dashboard.png)

### Gestión de Cuentas
![Cuentas](docs/screenshots/accounts.png)

### Bot de Telegram
![Bot](docs/screenshots/telegram-bot.png)

---

**Apliación creada usando Claude 4.5** 