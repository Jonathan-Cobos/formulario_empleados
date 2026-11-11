<?php
declare(strict_types=1);

const API_BASE_URL = 'http://localhost:8080';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function apiRequest(string $method, string $path, ?array $payload = null): array
{
    $baseUrl = rtrim((string) (getenv('SPRING_API_URL') ?: API_BASE_URL), '/');
    $headers = [
        'Accept: application/json',
        'Connection: close',
    ];
    $options = [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'timeout' => 8,
        'ignore_errors' => true,
    ];

    if ($payload !== null) {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['error' => 'No se pudo preparar la información del empleado.'];
        }
        $headers[] = 'Content-Type: application/json';
        $options['header'] = implode("\r\n", $headers);
        $options['content'] = $json;
    }

    $context = stream_context_create(['http' => $options]);
    $body = @file_get_contents($baseUrl . $path, false, $context);
    $responseHeaders = $http_response_header ?? [];

    if ($body === false) {
        $lastError = error_get_last();
        $detail = is_array($lastError) ? ': ' . $lastError['message'] : '';
        return ['error' => 'No se pudo contactar al backend Spring' . $detail];
    }

    $status = 0;
    foreach ($responseHeaders as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
            $status = (int) $matches[1];
        }
    }
    if ($status === 0) {
        return ['error' => 'El backend Spring devolvió una respuesta HTTP inválida.'];
    }

    $data = json_decode($body, true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($data)
            ? ($data['detail'] ?? $data['message'] ?? null)
            : null;
        return ['error' => is_string($message) ? $message : 'El backend rechazó la solicitud (HTTP ' . $status . ').'];
    }

    return ['data' => $data];
}

function formOptions(): array
{
    return [
        'areas' => apiRequest('GET', '/api/areas'),
        'roles' => apiRequest('GET', '/api/roles'),
    ];
}

function employeePayload(array $form): array
{
    return [
        'nombre' => $form['nombre'],
        'correo' => $form['correo'],
        'sexo' => $form['sexo'],
        'area' => ['id' => (int) $form['area_id']],
        'boletin' => (int) $form['boletin'],
        'description' => $form['description'],
        'roles' => [['id' => (int) $form['rol_id']]],
    ];
}

function isValidEmployeeForm(array $form, array $areas, array $roles): string
{
    if (!preg_match('/^[\p{L} ]{1,255}$/u', $form['nombre'])) {
        return 'El nombre es obligatorio y solo puede contener letras y espacios.';
    }
    if (!filter_var($form['correo'], FILTER_VALIDATE_EMAIL) || strlen($form['correo']) > 255) {
        return 'Escribe un correo electrónico válido.';
    }
    if (!in_array($form['sexo'], ['m', 'f'], true)) {
        return 'Selecciona una opción de sexo.';
    }
    if (!in_array($form['area_id'], array_map(
        static fn (array $area): string => (string) $area['id'],
        $areas
    ), true)) {
        return 'Selecciona un área disponible.';
    }
    if ($form['description'] === '') {
        return 'La descripción es obligatoria.';
    }
    if (!in_array($form['rol_id'], array_map(
        static fn (array $rol): string => (string) $rol['id'],
        $roles
    ), true)) {
        return 'Selecciona un rol disponible.';
    }
    return '';
}

function readEmployeeForm(): array
{
    return [
        'nombre' => trim((string) ($_POST['nombre'] ?? '')),
        'correo' => trim((string) ($_POST['correo'] ?? '')),
        'sexo' => trim((string) ($_POST['sexo'] ?? '')),
        'area_id' => trim((string) ($_POST['area_id'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'boletin' => isset($_POST['boletin']) && $_POST['boletin'] === '1' ? '1' : '0',
        'rol_id' => trim((string) ($_POST['rol_id'] ?? '')),
    ];
}
