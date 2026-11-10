# Backend de gestión de empleados

API REST construida con Spring Boot, Spring Data JPA y MySQL.

## Requisitos

- JDK 21 o superior.
- MySQL en ejecución.
- Base de datos `lista_empleados` (el conector intenta crearla si no existe y el usuario tiene permisos).

No es necesario instalar Maven globalmente: el proyecto incluye Maven Wrapper.

## Configurar la conexión a MySQL

La conexión se configura en `src/main/resources/application.properties`. Por defecto usa:

- Host y puerto: `localhost:3306`
- Base de datos: `lista_empleados`
- Usuario: `root`
- Contraseña: la variable de entorno `MYSQL_PASSWORD`

En PowerShell, desde la carpeta del proyecto, establece la contraseña en la sesión actual e inicia la aplicación:

```powershell
$env:MYSQL_PASSWORD = Read-Host "Contraseña de MySQL"
.\mvnw.cmd spring-boot:run
```

La variable solo queda definida en esa ventana de PowerShell. No guardes contraseñas en el código ni en este archivo.

Para compilar y ejecutar las pruebas:

```powershell
.\mvnw.cmd clean test
```

## Base de datos

Hibernate está configurado con `ddl-auto=update`; al iniciar, crea o actualiza las tablas según las entidades:

- `areas`: `id`, `nombre`
- `empleados`: `id`, `nombre`, `sexo`, `area_id`, `boletin`, `description`
- `roles`: `id`, `nombre`
- `empleado_rol`: `empleado_id`, `rol_id`

`empleados.area_id` es opcional. Los nombres asumidos para las columnas descritas como `vombre` e `ip` son `nombre` e `id`, respectivamente.

## API REST

La aplicación expone operaciones CRUD para estas rutas:

| Recurso | Ruta |
| --- | --- |
| Áreas | `/api/areas` |
| Empleados | `/api/empleados` |
| Roles | `/api/roles` |

Para cada ruta están disponibles:

- `GET /api/{recurso}`: listar.
- `GET /api/{recurso}/{id}`: consultar por id.
- `POST /api/{recurso}`: crear.
- `PUT /api/{recurso}/{id}`: actualizar.
- `DELETE /api/{recurso}/{id}`: eliminar.

Ejemplo para crear un área:

```http
POST /api/areas
Content-Type: application/json
```

```json
{
  "nombre": "Sistemas"
}
```

Ejemplo para crear un rol:

```json
{
  "nombre": "Administrador"
}
```

Ejemplo para crear un empleado (el área y cada rol deben existir previamente):

```json
{
  "nombre": "Ana Pérez",
  "sexo": "F",
  "area": {
    "id": 1
  },
  "boletin": 1,
  "description": "Descripción del empleado",
  "roles": [
    {
      "id": 1
    }
  ]
}
```

Para un empleado sin área, se puede enviar `"area": null`. Para no asignar roles, se puede enviar `"roles": []`.
