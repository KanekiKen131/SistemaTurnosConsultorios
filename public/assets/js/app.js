(() => {
    'use strict';

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    const csrfToken = $('meta[name="csrf-token"]')?.content ?? '';
    const page = document.body.dataset.page ?? '';
    const ESTADOS = {
        ESPERA: { label: 'En espera', badge: 'badge-espera' },
        ATENDIENDO: { label: 'Atendiendo', badge: 'badge-atendiendo' },
        ATENDIDO: { label: 'Atendido', badge: 'badge-atendido' },
        AUSENTE: { label: 'Ausente', badge: 'badge-ausente' },
    };

    let toastTimer = null;

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[char]));
    }

    async function api(action, params = {}, method = 'GET') {
        let url = '?action=' + encodeURIComponent(action);
        const options = {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json' },
        };

        if (method === 'GET') {
            const query = new URLSearchParams();
            Object.entries(params || {}).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '') {
                    query.append(key, String(value));
                }
            });
            const serialized = query.toString();
            if (serialized !== '') {
                url += '&' + serialized;
            }
        } else {
            const body = new URLSearchParams();
            Object.entries(params || {}).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    body.append(key, String(value));
                }
            });
            options.method = 'POST';
            options.headers['X-CSRF-Token'] = csrfToken;
            options.body = body;
        }

        const response = await fetch(url, options);
        let data = {};

        try {
            data = await response.json();
        } catch (_) {
            data = {};
        }

        if (response.status === 401) {
            window.location.href = '?action=login';
            throw new Error('Su sesión expiró. Inicie sesión nuevamente.');
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'No se pudo completar la operación.');
        }

        return data;
    }

    function toast(message, type = 'success') {
        const element = $('#toast');
        if (!element) {
            return;
        }

        element.textContent = message;
        element.className = 'toast ' + type;
        element.hidden = false;

        clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            element.hidden = true;
        }, 4500);
    }

    function showAlert(element, message, type = 'error') {
        if (!element) {
            return;
        }

        if (!message) {
            element.hidden = true;
            return;
        }

        element.textContent = message;
        element.className = 'alert alert-' + type;
        element.hidden = false;
    }

    function parseDate(value) {
        if (!value) {
            return null;
        }

        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function fmtTime(value) {
        const date = parseDate(value);
        return date ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '—';
    }

    function estadoBadge(estado) {
        const info = ESTADOS[estado] || { label: estado || '—', badge: 'badge-espera' };
        return `<span class="badge ${info.badge}">${esc(info.label)}</span>`;
    }

    function roleBadge(role) {
        const labels = {
            admin: 'Administrador',
            recepcion: 'Recepción',
            medico: 'Médico',
        };
        return `<span class="badge role-${esc(role)}">${esc(labels[role] || role)}</span>`;
    }

    function initGlobal() {
        $$('[data-logout]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (!window.confirm('¿Desea cerrar sesión?')) {
                    return;
                }

                try {
                    const data = await api('api_logout', {}, 'POST');
                    window.location.href = data.redirect || '?action=login';
                } catch (error) {
                    toast(error.message, 'error');
                }
            });
        });
    }

    function initLogin() {
        const form = $('#loginForm');
        if (!form) {
            return;
        }

        const errorBox = $('#loginError');
        const emailInput = $('#email');
        const submit = form.querySelector('button[type="submit"]');

        if (emailInput) {
            emailInput.focus();
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            showAlert(errorBox, '');

            const originalText = submit.textContent;
            submit.disabled = true;
            submit.textContent = 'Ingresando…';

            try {
                const data = await api('api_login', {
                    email: emailInput.value,
                    password: $('#password').value,
                }, 'POST');

                window.location.href = data.redirect || '?action=home';
            } catch (error) {
                showAlert(errorBox, error.message);
                submit.disabled = false;
                submit.textContent = originalText;
            }
        });
    }

    function renderQueue(container, turnos) {
        if (!container) {
            return;
        }

        if (!turnos || turnos.length === 0) {
            container.innerHTML = '<p class="empty-state">No hay turnos en espera por ahora.</p>';
            return;
        }

        const fragment = document.createDocumentFragment();

        turnos.forEach((turno) => {
            const item = document.createElement('div');
            item.className = 'queue-item state-' + String(turno.estado || 'ESPERA').toLowerCase();

            const code = document.createElement('span');
            code.className = 'queue-code';
            code.textContent = turno.codigo;

            const main = document.createElement('div');
            main.className = 'queue-main';

            const patient = document.createElement('strong');
            patient.textContent = turno.paciente || 'Sin nombre registrado';

            const detail = document.createElement('span');
            detail.textContent = `${turno.especialidad?.nombre || 'Especialidad'} · Emitido ${fmtTime(turno.fecha_creacion)}`;

            main.append(patient, detail);

            const time = document.createElement('span');
            time.className = 'queue-time';
            time.textContent = fmtTime(turno.fecha_creacion);

            item.append(code, main, time);
            fragment.append(item);
        });

        container.replaceChildren(fragment);
    }

    function initRecepcion() {
        const form = $('#formTurno');
        if (!form) {
            return;
        }

        const especialidad = $('#especialidad');
        const paciente = $('#paciente');
        const ticket = $('#ticket');
        const cola = $('#colaEspera');
        const submit = form.querySelector('button[type="submit"]');
        const btnImprimir = $('#btnImprimir');
        const btnNuevo = $('#btnNuevo');
        const btnRefresh = $('#btnRefreshCola');

        const cargarCola = async (silencioso = false) => {
            try {
                const data = await api('api_cola');
                renderQueue(cola, data.cola);
            } catch (error) {
                if (!silencioso) {
                    toast(error.message, 'error');
                }
            }
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (especialidad.value === '') {
                toast('Seleccione una especialidad.', 'error');
                especialidad.focus();
                return;
            }

            const originalText = submit.textContent;
            submit.disabled = true;
            submit.textContent = 'Generando…';

            try {
                const data = await api('api_crear_turno', {
                    especialidad_id: especialidad.value,
                    paciente: paciente.value.trim(),
                }, 'POST');

                const turno = data.turno;

                $('#ticketCodigo').textContent = turno.codigo;
                $('#ticketPaciente').textContent = turno.paciente
                    ? `Paciente: ${turno.paciente}`
                    : 'Sin nombre registrado';
                $('#ticketEspecialidad').textContent =
                    `${turno.especialidad.nombre} (${turno.especialidad.prefijo})`;
                $('#ticketHora').textContent = `Emitido a las ${fmtTime(turno.fecha_creacion)}`;

                ticket.hidden = false;
                ticket.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                paciente.value = '';
                paciente.focus();

                await cargarCola(true);
                toast(`Turno ${turno.codigo} generado.`);
            } catch (error) {
                toast(error.message, 'error');
            } finally {
                submit.disabled = false;
                submit.textContent = originalText;
            }
        });

        btnImprimir?.addEventListener('click', () => window.print());

        btnNuevo?.addEventListener('click', () => {
            ticket.hidden = true;
            paciente.value = '';
            especialidad.focus();
        });

        btnRefresh?.addEventListener('click', () => cargarCola());

        cargarCola(true);
        window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                cargarCola(true);
            }
        }, 5000);
    }

    function initMedico() {
        const btnLlamar = $('#btnLlamar');
        if (!btnLlamar) {
            return;
        }

        const btnAusente = $('#btnAusente');
        const btnFinalizar = $('#btnFinalizar');
        const btnRefresh = $('#btnRefreshMedico');
        const cola = $('#colaMedico');
        const currentCode = $('.current-code');
        const currentPatient = $('.current-patient');
        const currentMeta = $('.current-meta');
        const estadoPill = $('#estadoPill');

        let turnoActualId = null;

        const renderActual = (turno) => {
            turnoActualId = turno?.id ?? null;

            if (currentCode) {
                currentCode.textContent = turno ? turno.codigo : '---';
                currentCode.classList.toggle('muted', !turno);
            }

            if (currentPatient) {
                currentPatient.textContent = turno
                    ? (turno.paciente || 'Paciente sin nombre')
                    : 'Sin paciente en atención';
                currentPatient.classList.toggle('muted', !turno);
            }

            if (currentMeta) {
                currentMeta.textContent = turno
                    ? `Llamado ${fmtTime(turno.fecha_llamado)}`
                    : 'Presione «Llamar siguiente paciente»';
            }

            if (estadoPill) {
                estadoPill.className = turno ? 'badge badge-atendiendo' : 'badge badge-espera';
                estadoPill.textContent = turno ? 'Atendiendo' : 'Sin turno';
            }

            if (btnAusente) {
                btnAusente.disabled = !turno;
            }

            if (btnFinalizar) {
                btnFinalizar.disabled = !turno;
            }
        };

        const cargar = async () => {
            try {
                const [actual, espera] = await Promise.all([
                    api('api_turno_actual'),
                    api('api_cola'),
                ]);

                renderActual(actual.turno);
                renderQueue(cola, espera.cola);
            } catch (error) {
                toast(error.message, 'error');
            }
        };

        btnLlamar.addEventListener('click', async () => {
            btnLlamar.disabled = true;

            try {
                const data = await api('api_llamar_siguiente', {}, 'POST');

                toast(data.message, data.turno ? 'success' : 'error');
                await cargar();
            } catch (error) {
                toast(error.message, 'error');
            } finally {
                btnLlamar.disabled = false;
            }
        });

        btnAusente?.addEventListener('click', async () => {
            if (!turnoActualId) {
                return;
            }

            if (!window.confirm('¿Marcar al paciente actual como ausente y llamar al siguiente?')) {
                return;
            }

            btnAusente.disabled = true;

            try {
                const data = await api('api_marcar_ausente', {
                    turno_id: turnoActualId,
                }, 'POST');

                toast(data.message);
                await cargar();
            } catch (error) {
                toast(error.message, 'error');
                btnAusente.disabled = false;
            }
        });

        btnFinalizar?.addEventListener('click', async () => {
            if (!turnoActualId) {
                return;
            }

            if (!window.confirm('¿Finalizar la atención del paciente actual?')) {
                return;
            }

            btnFinalizar.disabled = true;

            try {
                const data = await api('api_finalizar', {
                    turno_id: turnoActualId,
                }, 'POST');

                toast(data.message);
                await cargar();
            } catch (error) {
                toast(error.message, 'error');
                btnFinalizar.disabled = false;
            }
        });

        btnRefresh?.addEventListener('click', cargar);

        cargar();
        window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                cargar();
            }
        }, 5000);
    }

    function initAdmin() {
        const tbody = $('#tablaUsuarios');
        if (!tbody) {
            return;
        }

        const dialog = $('#userDialog');
        const form = $('#userForm');
        const formError = $('#userFormError');
        const title = $('#userDialogTitle');
        const idInput = $('#usuarioId');
        const nombreInput = $('#usuarioNombre');
        const emailInput = $('#usuarioEmail');
        const rolInput = $('#usuarioRol');
        const especialidadInput = $('#usuarioEspecialidad');
        const consultorioInput = $('#usuarioConsultorio');
        const passwordInput = $('#usuarioPassword');
        const activoInput = $('#usuarioActivo');
        const passwordLabel = $('#passwordLabel');
        const passwordHelp = $('#passwordHelp');
        const fieldEspecialidad = $('#fieldEspecialidad');
        const btnNuevo = $('#btnNuevoUsuario');
        const btnRefresh = $('#btnRefreshUsuarios');

        const especialidades = new Map();
        $$('#usuarioEspecialidad option').forEach((option) => {
            if (option.value !== '0') {
                especialidades.set(
                    Number(option.value),
                    option.textContent.trim().replace(/\s*\([^)]*\)\s*$/, '')
                );
            }
        });

        let usuarios = [];

        const toggleMedicoFields = () => {
            const esMedico = rolInput.value === 'medico';
            fieldEspecialidad.hidden = !esMedico;
            especialidadInput.required = esMedico;
            consultorioInput.required = esMedico;

            if (!esMedico) {
                especialidadInput.value = '0';
                consultorioInput.value = '';
            }
        };

        const abrirFormulario = (usuario = null) => {
            form.reset();
            showAlert(formError, '');
            idInput.value = usuario ? usuario.id : '0';

            if (usuario) {
                title.textContent = 'Editar usuario';
                nombreInput.value = usuario.nombre;
                emailInput.value = usuario.email;
                rolInput.value = usuario.rol;
                especialidadInput.value = usuario.especialidad_id ? String(usuario.especialidad_id) : '0';
                consultorioInput.value = usuario.consultorio || '';
                activoInput.checked = Boolean(usuario.activo);
                passwordInput.required = false;
                passwordLabel.textContent = 'Nueva contraseña (opcional)';
                passwordHelp.textContent = 'Dejar en blanco para conservar la contraseña actual.';
            } else {
                title.textContent = 'Nuevo usuario';
                rolInput.value = 'recepcion';
                activoInput.checked = true;
                passwordInput.required = true;
                passwordLabel.textContent = 'Contraseña';
                passwordHelp.textContent = 'Mínimo 8 caracteres.';
            }

            toggleMedicoFields();
            dialog.showModal();
            nombreInput.focus();
        };

        const renderUsuarios = () => {
            tbody.replaceChildren();

            if (usuarios.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 7;
                cell.className = 'muted';
                cell.textContent = 'No hay usuarios registrados.';
                row.append(cell);
                tbody.append(row);
                return;
            }

            usuarios.forEach((usuario) => {
                const row = document.createElement('tr');
                if (!usuario.activo) {
                    row.className = 'row-inactive';
                }

                const nombre = document.createElement('td');
                nombre.textContent = usuario.nombre;

                const email = document.createElement('td');
                email.textContent = usuario.email;

                const roleCell = document.createElement('td');
                roleCell.innerHTML = roleBadge(usuario.rol);

                const especialidad = document.createElement('td');
                especialidad.textContent = usuario.rol === 'medico'
                    ? (especialidades.get(Number(usuario.especialidad_id)) || '—')
                    : '—';

                const consultorio = document.createElement('td');
                consultorio.textContent = usuario.consultorio || '—';

                const estado = document.createElement('td');
                estado.innerHTML = usuario.activo
                    ? '<span class="badge badge-atendiendo">Activo</span>'
                    : '<span class="badge badge-ausente">Inactivo</span>';

                const acciones = document.createElement('td');
                acciones.className = 'right';
                const wrapper = document.createElement('div');
                wrapper.className = 'table-actions';

                const edit = document.createElement('button');
                edit.type = 'button';
                edit.className = 'btn btn-ghost btn-sm';
                edit.dataset.action = 'edit';
                edit.dataset.id = String(usuario.id);
                edit.textContent = 'Editar';

                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'btn btn-ghost btn-sm';
                toggle.dataset.action = 'toggle';
                toggle.dataset.id = String(usuario.id);
                toggle.textContent = usuario.activo ? 'Desactivar' : 'Activar';

                const reset = document.createElement('button');
                reset.type = 'button';
                reset.className = 'btn btn-ghost btn-sm';
                reset.dataset.action = 'reset';
                reset.dataset.id = String(usuario.id);
                reset.textContent = 'Clave';

                wrapper.append(edit, toggle, reset);
                acciones.append(wrapper);

                row.append(nombre, email, roleCell, especialidad, consultorio, estado, acciones);
                tbody.append(row);
            });
        };

        const cargarUsuarios = async () => {
            try {
                const data = await api('api_usuarios');
                usuarios = data.usuarios;
                renderUsuarios();
            } catch (error) {
                toast(error.message, 'error');
                tbody.innerHTML = '<tr><td colspan="7" class="muted">No se pudieron cargar los usuarios.</td></tr>';
            }
        };

        rolInput.addEventListener('change', toggleMedicoFields);

        btnNuevo?.addEventListener('click', () => abrirFormulario());
        btnRefresh?.addEventListener('click', cargarUsuarios);

        $$('[data-close-dialog]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        tbody.addEventListener('click', async (event) => {
            const button = event.target.closest('button[data-action]');
            if (!button) {
                return;
            }

            const id = Number(button.dataset.id);
            const usuario = usuarios.find((item) => item.id === id);
            if (!usuario) {
                return;
            }

            if (button.dataset.action === 'edit') {
                abrirFormulario(usuario);
                return;
            }

            if (button.dataset.action === 'toggle') {
                const nuevoEstado = !usuario.activo;
                if (!window.confirm(`¿${nuevoEstado ? 'Activar' : 'Desactivar'} la cuenta de ${usuario.nombre}?`)) {
                    return;
                }

                try {
                    const data = await api('api_usuario_estado', {
                        id: usuario.id,
                        activo: nuevoEstado ? 1 : 0,
                    }, 'POST');

                    toast(data.message);
                    await cargarUsuarios();
                } catch (error) {
                    toast(error.message, 'error');
                }

                return;
            }

            if (button.dataset.action === 'reset') {
                const password = window.prompt(`Nueva contraseña para ${usuario.nombre}:`);
                if (password === null) {
                    return;
                }

                if (password.length < 8) {
                    toast('La contraseña debe tener al menos 8 caracteres.', 'error');
                    return;
                }

                try {
                    const data = await api('api_usuario_password', {
                        id: usuario.id,
                        password,
                    }, 'POST');

                    toast(data.message);
                } catch (error) {
                    toast(error.message, 'error');
                }
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            showAlert(formError, '');

            const id = Number(idInput.value);
            const esMedico = rolInput.value === 'medico';
            const submit = form.querySelector('button[type="submit"]');
            const originalText = submit.textContent;

            submit.disabled = true;
            submit.textContent = 'Guardando…';

            try {
                const data = await api('api_usuario_guardar', {
                    id,
                    nombre: nombreInput.value.trim(),
                    email: emailInput.value.trim(),
                    rol: rolInput.value,
                    activo: activoInput.checked ? 1 : 0,
                    especialidad_id: esMedico ? especialidadInput.value : 0,
                    consultorio: esMedico ? consultorioInput.value.trim() : '',
                    password: passwordInput.value,
                }, 'POST');

                dialog.close();
                toast(data.message);
                await cargarUsuarios();
            } catch (error) {
                showAlert(formError, error.message);
            } finally {
                submit.disabled = false;
                submit.textContent = originalText;
            }
        });

        cargarUsuarios();
    }

    function initHistorial() {
        const form = $('#historialFiltros');
        if (!form) {
            return;
        }

        const tbody = $('#tablaHistorial');
        const diaInput = $('#historialDia');
        const especialidadInput = $('#historialEspecialidad');
        const estadoInput = $('#historialEstado');
        const contador = $('#historialContador');
        const titulo = $('#historialTitulo');
        const btnImprimir = $('#btnImprimirHistorial');

        const resumen = {
            total: $('#sumTotal'),
            ESPERA: $('#sumEspera'),
            ATENDIENDO: $('#sumAtendiendo'),
            ATENDIDO: $('#sumAtendido'),
            AUSENTE: $('#sumAusente'),
        };

        const renderResumen = (data) => {
            resumen.total.textContent = data.total;
            resumen.ESPERA.textContent = data.ESPERA;
            resumen.ATENDIENDO.textContent = data.ATENDIENDO;
            resumen.ATENDIDO.textContent = data.ATENDIDO;
            resumen.AUSENTE.textContent = data.AUSENTE;
        };

        const renderTabla = (turnos, dia) => {
            tbody.replaceChildren();
            contador.textContent = `${turnos.length} turno${turnos.length === 1 ? '' : 's'}`;
            titulo.textContent = `Turnos del ${dia}`;

            if (turnos.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 9;
                cell.className = 'muted';
                cell.textContent = 'No hay turnos para los filtros seleccionados.';
                row.append(cell);
                tbody.append(row);
                return;
            }

            turnos.forEach((turno) => {
                const row = document.createElement('tr');

                const codigo = document.createElement('td');
                codigo.className = 'code-cell';
                codigo.textContent = turno.codigo;

                const paciente = document.createElement('td');
                paciente.textContent = turno.paciente || '—';

                const especialidad = document.createElement('td');
                especialidad.textContent = turno.especialidad?.nombre || '—';

                const medico = document.createElement('td');
                medico.textContent = turno.medico?.nombre || '—';

                const consultorio = document.createElement('td');
                consultorio.textContent = turno.consultorio || '—';

                const estado = document.createElement('td');
                estado.innerHTML = estadoBadge(turno.estado);

                const emision = document.createElement('td');
                emision.textContent = fmtTime(turno.fecha_creacion);

                const llamado = document.createElement('td');
                llamado.textContent = fmtTime(turno.fecha_llamado);

                const fin = document.createElement('td');
                fin.textContent = fmtTime(turno.fecha_fin);

                row.append(codigo, paciente, especialidad, medico, consultorio, estado, emision, llamado, fin);
                tbody.append(row);
            });
        };

        const cargar = async () => {
            try {
                const data = await api('api_historial', {
                    dia: diaInput.value,
                    especialidad_id: especialidadInput.value,
                    estado: estadoInput.value,
                });

                renderResumen(data.resumen);
                renderTabla(data.turnos, data.dia);
            } catch (error) {
                toast(error.message, 'error');
                tbody.innerHTML = '<tr><td colspan="9" class="muted">No se pudo cargar el historial.</td></tr>';
            }
        };

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            cargar();
        });

        btnImprimir?.addEventListener('click', () => window.print());

        cargar();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initGlobal();

        switch (page) {
            case 'login':
                initLogin();
                break;
            case 'recepcion':
                initRecepcion();
                break;
            case 'medico':
                initMedico();
                break;
            case 'admin':
                initAdmin();
                break;
            case 'historial':
                initHistorial();
                break;
            default:
                break;
        }
    });
})();
