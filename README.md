# Catefería POS

Una aplicación web moderna para gestión integral de catálogos de bebidas, productos y ventas. Sistema completo de punto de venta (POS) con autenticación segura, gestión de inventario y reportes en tiempo real.

## Características

- 🍹 **Gestión de Catálogo**: Administra bebidas, productos, categorías y personalizaciones
- 💰 **Sistema de Ventas (POS)**: Interfaz intuitiva para registrar ventas en tiempo real
- 👥 **Gestión de Usuarios**: Control de acceso basado en roles (Fortify + Sanctum)
- 📊 **Reportes**: Análisis de ventas, estadísticas y métricas de negocio
- 🏢 **Multi-sucursal**: Soporte para múltiples locales o puntos de venta
- ⚙️ **Personalizaciones**: Opciones y tipos de personalización para productos
- 👨‍💼 **Gestión de Equipo**: Administración de empleados y sesiones de trabajo
- 📱 **Interfaz Reactiva**: Experiencia de usuario fluida con Livewire y Flux UI
- 🎨 **Diseño Responsivo**: Interfaz adaptada para diferentes dispositivos

## Stack Tecnológico

### Backend
- **Laravel 13** - Framework PHP moderno
- **PHP 8.4** - Lenguaje de programación
- **Livewire 4** - Componentes reactivos en tiempo real
- **Laravel Fortify** - Autenticación sin vistas
- **Laravel Sanctum** - Tokens de API segura
- **Laravel AI** - Integración de IA

### Frontend
- **Flux UI 2** - Componentes UI profesionales
- **Livewire** - Interactividad sin JavaScript
- **Tailwind CSS 4** - Framework de estilos
- **Alpine.js** - Interacciones cliente

### Base de Datos
- **SQLite** (desarrollo) / **MySQL** (producción)
- **Eloquent ORM** - Mapeo objeto-relacional

### Testing y Calidad
- **Pest 4** - Framework de testing moderno
- **PHPUnit 12** - Testing PHP
- **Laravel Pint** - Formateador de código PHP

## Requisitos Previos

- PHP 8.4 o superior
- Composer 2.0+
- Node.js 18+
- npm o yarn
- Base de datos (SQLite para desarrollo, MySQL/PostgreSQL para producción)

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/BenjaminSierra09/cateferia20trece.git
cd cateferia20trece
```

### 2. Instalar dependencias

```bash
# Dependencias PHP
composer install

# Dependencias Node
npm install
```

### 3. Configurar variables de entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4. Configurar base de datos

Editar `.env` según tu entorno:

**Para desarrollo (SQLite):**
```env
DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite
```

**Para producción (MySQL):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cateferia
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Ejecutar migraciones

```bash
php artisan migrate --seed
```

### 6. Compilar assets

```bash
npm run build
```

## Uso

### Desarrollo

```bash
# Iniciar servidor de desarrollo
composer run dev

# En otra terminal, compilar assets en tiempo real
npm run dev
```

Acceder a `https://cateferia20trece.test` (con Laravel Herd) o `http://localhost:8000`

### Producción

```bash
npm run build
php artisan migrate --force
```

## Estructura del Proyecto

```
app/
├── Http/              # Controladores y Middleware
├── Livewire/          # Componentes Livewire
│   ├── Beverages/     # Gestión de bebidas
│   ├── Branches/      # Gestión de sucursales
│   ├── Catalog/       # Catálogo
│   ├── Categories/    # Gestión de categorías
│   ├── Customers/     # Gestión de clientes
│   ├── Products/      # Gestión de productos
│   ├── Sales/         # Sistema de ventas (POS)
│   ├── Reports/       # Reportes
│   ├── Team/          # Gestión de equipo
│   └── WorkSession/   # Sesiones de trabajo
├── Models/            # Modelos Eloquent
├── Actions/           # Acciones reutilizables
├── Enums/             # Enumeraciones
├── Jobs/              # Trabajos en cola
└── Services/          # Servicios de negocio

database/
├── migrations/        # Migraciones de BD
├── factories/         # Model factories
└── seeders/           # Datos de prueba

resources/
├── views/             # Vistas Blade
├── css/               # Estilos
└── js/                # Scripts

routes/
├── web.php            # Rutas web
├── api.php            # Rutas API
└── console.php        # Comandos Artisan

tests/
├── Feature/           # Tests funcionales
└── Unit/              # Tests unitarios
```

## Modelos Principales

- **User** - Usuarios del sistema
- **Beverage** - Bebidas disponibles
- **Product** - Productos/artículos
- **Branch** - Sucursales/locales
- **Category** - Categorías de productos
- **Size** - Tamaños disponibles
- **Customer** - Clientes
- **Sale** - Ventas registradas
- **WorkSession** - Sesiones de trabajo de empleados

## Características por Módulo

### 🍹 Beverages (Bebidas)
- CRUD completo de bebidas
- Asociación con categorías
- Precios por sucursal
- Precios por tamaño
- Generación automática de imágenes con IA

### 💰 Sales (Ventas)
- Interfaz POS intuitiva
- Carrito de compras dinámico
- Personalización de productos
- Múltiples métodos de pago
- Descuentos y promociones

### 📊 Reports (Reportes)
- Dashboard de visión general
- Análisis de ventas
- Estadísticas por período
- Exportación de datos

### 👥 Team (Equipo)
- Gestión de empleados
- Asignación de roles
- Control de permisos
- Registro de sesiones

## Testing

### Ejecutar todos los tests

```bash
php artisan test
```

### Ejecutar tests de un archivo

```bash
php artisan test tests/Feature/SalesTest.php
```

### Ejecutar tests con cobertura

```bash
php artisan test --coverage
```

### Formato compacto

```bash
php artisan test --compact
```

## Comandos Útiles

```bash
# Ver todas las rutas
php artisan route:list

# Ver configuración
php artisan config:show app.name

# Limpiar cachés
php artisan cache:clear
php artisan config:clear

# Generar API documentation
php artisan tinker

# Ejecutar seeder
php artisan db:seed

# Crear nuevo modelo
php artisan make:model ModelName -m

# Crear componente Livewire
php artisan make:livewire ComponentName
```

## Configuración

### Fortify (Autenticación)
Ver `config/fortify.php` para personalizar:
- Redireccionamientos después de login
- Rate limiting
- Características de 2FA

### Sanctum (API)
Ver `config/sanctum.php` para tokens de API

### AI Integration
Ver `config/ai.php` para configurar proveedores de IA

## Contribución

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Estándares de Código

- Seguir PSR-12 (Laravel Pint)
- Usar type hints en todas las funciones
- Documentar con PHPDoc
- Escribir tests para nuevas funcionalidades
- Mantener cobertura de tests > 80%

```bash
# Formatear código
vendor/bin/pint
```

## Solución de Problemas

### "Unable to locate file in Vite manifest"
```bash
npm run build
# o en desarrollo
npm run dev
```

### Errores de base de datos
```bash
php artisan migrate:refresh --seed
```

### Limpiar configuración
```bash
php artisan optimize:clear
```

## Licencia

Este proyecto está licenciado bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## Soporte

Para reportar bugs o solicitar features, abre un [Issue](https://github.com/tu-usuario/cateferia20trece/issues).

## Autores

- **Benjamin** - *Trabajo Inicial*

## Agradecimientos

- [Laravel](https://laravel.com/)
- [Livewire](https://livewire.laravel.com/)
- [Flux UI](https://flux.laravel.com/)
- [Tailwind CSS](https://tailwindcss.com/)
- Comunidad de Laravel
