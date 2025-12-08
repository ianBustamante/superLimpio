# Super Limpio - Sistema POS

Sistema de punto de venta para tienda de productos de limpieza.

## 🚀 Características

- ✅ Gestión de productos y categorías
- ✅ Sistema de usuarios (Cliente, Empleado, Administrador)
- ✅ Carrito de compras y ventas
- ✅ Reportes de ventas
- ✅ Recuperación de contraseña
- ✅ Control de inventario (stock)
- ✅ Registro de eventos y auditoría

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior / MariaDB
- Servidor web (Apache, Nginx)
- Extensiones PHP: mysqli, session

## 🔧 Instalación

### Opción 1: Instalación Local (XAMPP)

1. **Clonar o descargar el proyecto** en la carpeta `htdocs` de XAMPP:
   ```
   C:\xampp\htdocs\superLimpio
   ```

2. **Crear la base de datos:**
   - Abrir phpMyAdmin: http://localhost/phpmyadmin
   - Crear una base de datos llamada `productos_limpieza`
   - Importar el archivo: `assets/productos_limpieza.sql`

3. **Configurar la conexión** (Automático):
   - El archivo `includes/connection.php` detecta automáticamente si estás en local
   - Credenciales locales por defecto:
     - Usuario: `root`
     - Contraseña: `` (vacía)
     - Base de datos: `productos_limpieza`

4. **Acceder al sistema:**
   ```
   http://localhost/superLimpio/
   ```

### Opción 2: Instalación en Hostinger

1. **Subir archivos:**
   - Usar FTP o File Manager
   - Subir todos los archivos a `public_html/`

2. **Crear la base de datos:**
   - Panel de Hostinger → Bases de datos
   - Crear base de datos y usuario
   - Importar el archivo: `assets/productos_limpieza.sql`

3. **Configurar la conexión** (Automático):
   - El archivo `includes/connection.php` detecta automáticamente el entorno de Hostinger
   - Las credenciales de Hostinger ya están configuradas en el código

4. **Acceder al sistema:**
   ```
   https://tu-dominio.hostingersite.com/
   ```

## 👥 Usuarios de Prueba

### Administrador
- **Correo:** `admin@empresa.com`
- **Contraseña:** `Admin123`

### Empleado/Vendedor
- **Correo:** `ana@example.com`
- **Contraseña:** `Vendedor123`

### Cliente
- **Correo:** `juan@example.com`
- **Contraseña:** `Cliente123`

## 🗂️ Estructura del Proyecto

```
superLimpio/
├── assets/
│   ├── css/              # Estilos
│   ├── img/              # Imágenes
│   ├── js/               # JavaScript
│   └── productos_limpieza.sql  # Base de datos
├── dashboard/
│   ├── admin/            # Panel de administrador
│   ├── cliente/          # Panel de cliente
│   └── empleado/         # Panel de empleado
├── includes/
│   ├── connection.php    # Conexión a BD (auto-detección)
│   └── functions.php     # Funciones del sistema
├── modules/
│   ├── login.php         # Inicio de sesión
│   ├── logout.php        # Cerrar sesión
│   ├── registro.php      # Registro de usuarios
│   └── recuperar.php     # Recuperar contraseña
└── index.php             # Página principal
```

## 🔄 Actualización desde GitHub

Si haces cambios locales y quieres actualizarlos en Hostinger:

```bash
# 1. Commit de cambios locales
git add .
git commit -m "Descripción de los cambios"
git push

# 2. En Hostinger (si tienes acceso SSH)
git pull origin main
```

O subir manualmente los archivos modificados por FTP.

## 🛠️ Desarrollo

### Configuración Automática de Entorno

El archivo `includes/connection.php` detecta automáticamente:
- ✅ **Local (XAMPP):** Usa `root` sin contraseña
- ✅ **Hostinger:** Usa las credenciales de producción

No necesitas cambiar configuraciones al mover entre local y producción.

### Base de Datos

Todas las tablas están en **minúsculas** para compatibilidad:
- `categoria`
- `producto`
- `cliente`
- `empleado`
- `usuario`
- `venta`
- `detalleventa`
- `sesion`
- `recuperacion`
- `registroeventos`

## 📝 Notas Importantes

- ⚠️ Las contraseñas están hasheadas con `password_hash()` de PHP
- 🔒 Todas las consultas SQL usan **prepared statements** para prevenir SQL injection
- 📊 El sistema registra todos los eventos importantes en la tabla `registroeventos`
- 🔑 Los códigos de recuperación expiran en 15 minutos

## 🐛 Solución de Problemas

### Error: "Access denied for user 'root'@'localhost'"
- Verifica que MySQL esté corriendo en XAMPP
- Verifica las credenciales en `includes/connection.php`

### Error: "Table doesn't exist"
- Asegúrate de haber importado el archivo SQL
- Verifica que las tablas estén en minúsculas

### No detecta el entorno correctamente
- Revisa la variable `$_SERVER['SERVER_NAME']` en `includes/connection.php`
- Descomenta la línea de debug al final del archivo

## 📄 Licencia

Proyecto educativo - Super Limpio POS

## 👨‍💻 Autor

Ian Bustamante
- GitHub: [@ianBustamante](https://github.com/ianBustamante/superLimpio)
