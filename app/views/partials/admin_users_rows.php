<?php if (empty($usuarios)): ?>
    <tr><td colspan="7" class="muted">No hay usuarios registrados.</td></tr>
<?php else: ?>
    <?php foreach ($usuarios as $usuario): ?>
        <tr class="<?= $usuario->isActivo() ? '' : 'row-inactive' ?>">
            <td><?= e($usuario->getNombre()) ?></td>
            <td><?= e($usuario->getEmail()) ?></td>
            <td><span class="badge role-<?= e($usuario->getRol()) ?>"><?= e($rolEtiquetas[$usuario->getRol()] ?? $usuario->getRol()) ?></span></td>
            <td><?= e($usuario->getRol() === 'medico' ? ($espPorId[$usuario->getEspecialidadId()] ?? '—') : '—') ?></td>
            <td><?= e($usuario->getConsultorio() ?: '—') ?></td>
            <td><span class="badge <?= $usuario->isActivo() ? 'badge-atendiendo' : 'badge-ausente' ?>"><?= $usuario->isActivo() ? 'Activo' : 'Inactivo' ?></span></td>
            <td class="right">
                <div class="table-actions">
                    <button type="button" class="btn btn-ghost btn-sm" data-action="edit" data-id="<?= e((string) $usuario->getId()) ?>">Editar</button>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="toggle" data-id="<?= e((string) $usuario->getId()) ?>"><?= $usuario->isActivo() ? 'Desactivar' : 'Activar' ?></button>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="reset" data-id="<?= e((string) $usuario->getId()) ?>">Clave</button>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
