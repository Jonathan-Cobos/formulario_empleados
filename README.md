# Comó ejecutar la aplicación

## Requisitos

- Java 21 o superior.
- MySQL en ejecución.
- PHP 8 o superior.

## 1. Iniciar el backend

- Asegurarse de que Spring Boot pueda correr en el PC.
- Iniciar un primer PowerShell en la carpeta "demo_formulario".
- Establecer las credenciales de la base de datos (MySQL) como variables de entorno digitando:

$env:MYSQL_USER = "root"
$env:MYSQL_PASSWORD = "tu_contraseña"

- (Esto lo hice con el fin de ser flexible para cada usuario al momento de probar el programa).
- Inicializar el backend con Spring:

.\mvnw spring-boot:run

- El backend se ejecuta de forma local (en localhost).
- Dejar el PowerShell ejecutándose en segundo plano.

## 2. Iniciar el front

- Iniciar un segundo PowerShell en la carpeta "formulario".
- Inicializar el front digitando:
php -S localhost:8000
- Dejar el PowerShell ejecutándose en segundo plano.
- Abrir esta URL "http://localhost:8000" en el navegador.

## 3. Alcance y restricciones

- El formulario maneja estos casos de uso:
1: Creación de empleados
2: Revisión de la lista de empleados registrados
3: Modificación de empleados
4: Eliminación de empleados

- Restricciones o posibles errores:
1: Si no se rellenan los espacios, se muestra una ventana de alerta, a excepción del boletín.
2: Si se modifican los datos y no se rellena el espacio, avisa con una ventana.
3: Si el espacio del correo se rellena sin un formato correcto, se avisa para hacer la corrección.
