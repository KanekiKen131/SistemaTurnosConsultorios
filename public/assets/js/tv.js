(() => {
    'use strict';

    const $ = (selector) => document.querySelector(selector);

    const atendiendoEl = $('#tvAtendiendo');
    const proximosEl = $('#tvProximos');
    const updatedEl = $('#tvActualizado');
    const conexionEl = $('#tvConexion');
    const relojEl = $('#tvReloj');
    const btnSonido = $('#btnSonido');
    const btnPantalla = $('#btnPantalla');

    let audioCtx = null;
    let soundEnabled = false;
    let initialized = false;
    let lastCodes = new Set();

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (text !== undefined) {
            node.textContent = text;
        }
        return node;
    }

    function fmtTime(value, seconds = false) {
        const date = value ? new Date(value) : new Date();
        const options = { hour: '2-digit', minute: '2-digit' };
        if (seconds) {
            options.second = '2-digit';
        }
        return Number.isNaN(date.getTime())
            ? '--:--'
            : date.toLocaleTimeString([], options);
    }

    function setConnection(online) {
        if (!conexionEl) {
            return;
        }

        conexionEl.className = online ? 'tv-connection' : 'tv-connection offline';
        conexionEl.replaceChildren(
            el('span', 'dot'),
            document.createTextNode(online ? ' Conectado' : ' Sin conexión')
        );
    }

    function enableSound() {
        if (soundEnabled) {
            return;
        }

        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) {
            btnSonido.textContent = 'Sonido no disponible';
            return;
        }

        if (!audioCtx) {
            audioCtx = new AudioCtx();
        }

        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }

        soundEnabled = true;
        btnSonido.classList.add('active');
        btnSonido.textContent = 'Sonido activado';
        beep();
    }

    function disableSound() {
        soundEnabled = false;
        btnSonido.classList.remove('active');
        btnSonido.textContent = 'Activar sonido';
    }

    function beep() {
        if (!soundEnabled || !audioCtx) {
            return;
        }

        const start = audioCtx.currentTime;
        const notes = [880, 1174.66];

        notes.forEach((frequency, index) => {
            const offset = index * 0.28;
            const oscillator = audioCtx.createOscillator();
            const gain = audioCtx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;

            gain.gain.setValueAtTime(0.0001, start + offset);
            gain.gain.exponentialRampToValueAtTime(0.22, start + offset + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + offset + 0.24);

            oscillator.connect(gain);
            gain.connect(audioCtx.destination);
            oscillator.start(start + offset);
            oscillator.stop(start + offset + 0.26);
        });
    }

    function buildCurrentCard(turno, isNew) {
        const card = el('article', 'tv-card' + (isNew ? ' flash' : ''));

        const top = el('div', 'tv-card-top');
        top.append(
            el('span', 'tv-specialty', turno.especialidad?.nombre || 'Especialidad'),
            el('span', 'tv-room', turno.consultorio || turno.medico?.consultorio || 'Consultorio')
        );

        const code = el('div', 'tv-code', turno.codigo);

        const patient = el(
            'div',
            'tv-patient',
            turno.paciente ? `Paciente: ${turno.paciente}` : 'Paciente sin nombre'
        );

        const doctor = el(
            'div',
            'tv-doctor',
            turno.medico?.nombre ? `Atiende: ${turno.medico.nombre}` : 'Médico por asignar'
        );

        card.append(top, code, patient, doctor);

        return card;
    }

    function render(data) {
        const atendiendo = Array.isArray(data.atendiendo) ? data.atendiendo : [];
        const proximos = Array.isArray(data.proximos) ? data.proximos : [];

        const currentCodes = new Set(atendiendo.map((turno) => turno.codigo));
        const hasNew = initialized && atendiendo.some((turno) => !lastCodes.has(turno.codigo));

        if (atendiendoEl) {
            atendiendoEl.classList.toggle('many', atendiendo.length > 1);
            atendiendoEl.replaceChildren();

            if (atendiendo.length === 0) {
                const empty = el('div', 'tv-empty');
                empty.append(
                    el('span', 'tv-empty-code', '---'),
                    el('p', '', 'Esperando llamado de pacientes')
                );
                atendiendoEl.append(empty);
            } else {
                atendiendo.forEach((turno) => {
                    atendiendoEl.append(buildCurrentCard(turno, hasNew && !lastCodes.has(turno.codigo)));
                });
            }
        }

        if (proximosEl) {
            proximosEl.replaceChildren();

            if (proximos.length === 0) {
                proximosEl.append(el('li', 'tv-next-empty', 'Sin turnos en espera'));
            } else {
                proximos.forEach((turno) => {
                    const item = el('li');
                    item.append(
                        el('span', 'tv-next-code', turno.codigo),
                        el('span', 'tv-next-specialty', turno.especialidad?.nombre || 'Especialidad')
                    );
                    proximosEl.append(item);
                });
            }
        }

        if (updatedEl) {
            updatedEl.textContent = 'Actualizado ' + fmtTime(data.actualizado, true);
        }

        if (hasNew) {
            beep();
        }

        lastCodes = currentCodes;
        initialized = true;
    }

    async function load() {
        try {
            const response = await fetch('?action=api_tv', {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });

            const data = await response.json();

            if (!response.ok || data.success === false) {
                throw new Error('Respuesta no válida');
            }

            render(data);
            setConnection(true);
        } catch (_) {
            setConnection(false);
        }
    }

    function tickClock() {
        if (relojEl) {
            relojEl.textContent = fmtTime(null);
        }
    }

    btnSonido?.addEventListener('click', (event) => {
        event.stopPropagation();

        if (soundEnabled) {
            disableSound();
        } else {
            enableSound();
        }
    });

    document.addEventListener('click', () => {
        if (!soundEnabled) {
            enableSound();
        }
    });

    btnPantalla?.addEventListener('click', async (event) => {
        event.stopPropagation();

        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
        } catch (_) {
        }
    });

    document.addEventListener('fullscreenchange', () => {
        if (btnPantalla) {
            btnPantalla.textContent = document.fullscreenElement
                ? 'Salir de pantalla completa'
                : 'Pantalla completa';
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            load();
        }
    });

    tickClock();
    window.setInterval(tickClock, 1000);

    load();
    window.setInterval(load, 2000);
})();
