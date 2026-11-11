<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/api.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = match ((string) ($_GET['estado'] ?? '')) {
    'actualizado' => 'Los cambios se guardaron.',
    'eliminado' => 'El empleado aliminado.',
    default => '',
};
$editingId = filter_var($_GET['editar'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$form = [
    'nombre' => '',
    'correo' => '',
    'sexo' => '',
    'area_id' => '',
    'description' => '',
    'boletin' => '0',
    'rol_id' => '',
];

$options = formOptions();
$areas = $options['areas']['data'] ?? [];
$roles = $options['roles']['data'] ?? [];
$optionsAvailable = isset($options['areas']['data'], $options['roles']['data'])
    && is_array($areas)
    && is_array($roles);

if (!$optionsAvailable) {
    $error = $options['areas']['error'] ?? $options['roles']['error'] ?? 'No se pudieron cargar las opciones.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $action = (string) ($_POST['action'] ?? '');
    $postedId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if (!hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
        $error = 'La sesión venció. Recarga la página e inténtalo de nuevo.';
    } elseif (!$postedId || $postedId < 1) {
        $error = 'El identificador del empleado no es válido.';
    } elseif ($action === 'eliminar') {
        $response = apiRequest('DELETE', '/api/empleados/' . $postedId);
        if (isset($response['error'])) {
            $error = $response['error'];
        } else {
            header('Location: empleados.php?estado=eliminado', true, 303);
            exit;
        }
    } elseif ($action === 'actualizar') {
        $editingId = $postedId;
        $form = readEmployeeForm();
        if (!$optionsAvailable) {
            $error = 'No se pudo cargar.';
        } else {
            $error = isValidEmployeeForm($form, $areas, $roles);
        }

        if ($error === '') {
            $response = apiRequest('PUT', '/api/empleados/' . $editingId, employeePayload($form));
            if (isset($response['error'])) {
                $error = $response['error'];
            } else {
                header('Location: empleados.php?estado=actualizado', true, 303);
                exit;
            }
        }
    } else {
        $error = ' acción in valida.';
    }
}

$employeesResponse = apiRequest('GET', '/api/empleados');
$employees = $employeesResponse['data'] ?? [];
if (isset($employeesResponse['error'])) {
    $error = $error !== '' ? $error : $employeesResponse['error'];
} elseif (!is_array($employees)) {
    $employees = [];
    $error = 'fallo algo backend.';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $editingId > 0) {
    $employeeResponse = apiRequest('GET', '/api/empleados/' . $editingId);
    if (isset($employeeResponse['error'])) {
        $error = $employeeResponse['error'];
        $editingId = 0;
    } else {
        $employee = $employeeResponse['data'];
        $employeeRoles = $employee['roles'] ?? [];
        $form = [
            'nombre' => (string) ($employee['nombre'] ?? ''),
            'correo' => (string) ($employee['correo'] ?? ''),
            'sexo' => strtolower((string) ($employee['sexo'] ?? '')),
            'area_id' => (string) ($employee['area']['id'] ?? ''),
            'description' => (string) ($employee['description'] ?? ''),
            'boletin' => (string) ($employee['boletin'] ?? 0),
            'rol_id' => (string) ($employeeRoles[0]['id'] ?? ''),
        ];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lista de empleados</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="page page-wide">
        <section class="form-card employees-card" aria-labelledby="employees-title">
            <header class="form-header">
                <span class="eyebrow">Gestión de personal</span>
                <h1 id="employees-title">Lista de empleados</h1>
                <p>Consulta, modifica o elimina los empleados registrados.</p>
            </header>

            <nav class="page-actions" aria-label="Navegación">
                <a class="submit-button button-link" href="formulario.php">Crear</a>
            </nav>

            <?php if ($success !== ''): ?>
                <div class="notice notice-success" role="status"><?= escape($success) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="notice notice-error" role="alert"><?= escape($error) ?></div>
            <?php endif; ?>

            <?php if ($editingId > 0): ?>
                <section class="edit-panel" aria-labelledby="edit-title">
                    <h2 id="edit-title">Modificar empleado</h2>
                    <form method="post" action="lista-empleados.php?editar=<?= $editingId ?>">
                        <input type="hidden" name="csrf_token" value="<?= escape((string) $_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="actualizar">
                        <input type="hidden" name="id" value="<?= $editingId ?>">

                        <div class="edit-grid">
                            <div class="field">
                                <label for="nombre">Nombre completo</label>
                                <input id="nombre" name="nombre" type="text" value="<?= escape($form['nombre']) ?>" maxlength="255" pattern="[\p{L} ]+" required>
                            </div>
                            <div class="field">
                                <label for="correo">Correo electrónico</label>
                                <input id="correo" name="correo" type="email" value="<?= escape($form['correo']) ?>" maxlength="255" required>
                            </div>
                            <fieldset class="field choice-field">
                                <legend>Sexo</legend>
                                <div class="choice-row">
                                    <label class="choice-card">
                                        <input type="radio" name="sexo" value="m" <?= $form['sexo'] === 'm' ? 'checked' : '' ?> required>
                                        <span>Masculino</span>
                                    </label>
                                    <label class="choice-card">
                                        <input type="radio" name="sexo" value="f" <?= $form['sexo'] === 'f' ? 'checked' : '' ?>>
                                        <span>Femenino</span>
                                    </label>
                                </div>
                            </fieldset>
                            <div class="field">
                                <label for="area_id">Área</label>
                                <select id="area_id" name="area_id" required>
                                    <option value="">Selecciona un área</option>
                                    <?php foreach (is_array($areas) ? $areas : [] as $area): ?>
                                        <option value="<?= (int) ($area['id'] ?? 0) ?>" <?= $form['area_id'] === (string) ($area['id'] ?? '') ? 'selected' : '' ?>>
                                            <?= escape((string) ($area['nombre'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field edit-description">
                                <label for="description">Descripción</label>
                                <textarea id="description" name="description" rows="3" required><?= escape($form['description']) ?></textarea>
                            </div>
                            <label class="newsletter-option">
                                <input type="checkbox" name="boletin" value="1" <?= $form['boletin'] === '1' ? 'checked' : '' ?>>
                                <span>Deseo recibir boletín</span>
                            </label>
                            <fieldset class="field choice-field edit-roles">
                                <legend>Rol <span class="legend-hint">Selecciona uno</span></legend>
                                <div class="role-list">
                                    <?php foreach (is_array($roles) ? $roles : [] as $rol): ?>
                                        <label class="choice-card role-card">
                                            <input type="radio" name="rol_id" value="<?= (int) ($rol['id'] ?? 0) ?>" <?= $form['rol_id'] === (string) ($rol['id'] ?? '') ? 'checked' : '' ?> required>
                                            <span><?= escape((string) ($rol['nombre'] ?? '')) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        </div>
                        <div class="form-actions">
                            <button class="submit-button" type="submit" <?= !$optionsAvailable ? 'disabled' : '' ?>>Guardar cambios</button>
                            <a class="secondary-button" href="lista-empleados.php">Cancelar</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <div class="table-wrap">
                <table class="employees-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo electrónico</th>
                            <th>Sexo</th>
                            <th>Área</th>
                            <th>Descripción</th>
                            <th>Boletín</th>
                            <th>Rol</th>
                            <th>Modificar</th>
                            <th>Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees === []): ?>
                            <tr><td class="empty-state" colspan="9">No hay empleados registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <?php
                                $employeeId = (int) ($employee['id'] ?? 0);
                                $employeeRoles = $employee['roles'] ?? [];
                                $roleNames = array_map(
                                    static fn (array $role): string => (string) ($role['nombre'] ?? ''),
                                    is_array($employeeRoles) ? $employeeRoles : []
                                );
                                ?>
                                <tr>
                                    <td><?= escape((string) ($employee['nombre'] ?? '')) ?></td>
                                    <td><?= escape((string) ($employee['correo'] ?? '')) ?></td>
                                    <td><?= strtolower((string) ($employee['sexo'] ?? '')) === 'm' ? 'Masculino' : 'Femenino' ?></td>
                                    <td><?= escape((string) ($employee['area']['nombre'] ?? '')) ?></td>
                                    <td class="description-cell"><?= escape((string) ($employee['description'] ?? '')) ?></td>
                                    <td><?= (int) ($employee['boletin'] ?? 0) === 1 ? 'Sí' : 'No' ?></td>
                                    <td><?= escape(implode(', ', $roleNames)) ?></td>
                                    <td>
                                        <a class="table-action icon-action" href="lista-empleados.php?editar=<?= $employeeId ?>" aria-label="Modificar empleado" title="Modificar">
                                            <svg class="action-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M12 20h9"></path>
                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"></path>
                                            </svg>
                                            <span class="visually-hidden">Modificar</span>
                                        </a>
                                    </td>
                                    <td>
                                        <form method="post" action="lista-empleados.php" onsubmit="return confirm('Eliminar empleado');">
                                            <input type="hidden" name="csrf_token" value="<?= escape((string) $_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $employeeId ?>">
                                            <button class="table-action icon-action" type="submit" aria-label="Eliminar empleado" title="Eliminar">
                                                <svg class="action-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M8 6V4h8v2"></path>
                                                    <path d="m19 6-1 14H6L5 6"></path>
                                                    <path d="M10 11v5M14 11v5"></path>
                                                </svg>
                                                <span class="visually-hidden">Eliminar</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
