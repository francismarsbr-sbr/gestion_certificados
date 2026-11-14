(function () {
  const body = document.body;

  const abrirMenu = document.getElementById('abrir-menu');
  const fondo = document.getElementById('fondo-menu');
  if (abrirMenu) abrirMenu.addEventListener('click', () => body.classList.add('menu-abierto'));
  if (fondo) fondo.addEventListener('click', () => body.classList.remove('menu-abierto'));
  document.querySelectorAll('.lateral a').forEach((enlace) => {
    enlace.addEventListener('click', () => body.classList.remove('menu-abierto'));
  });

  function modal(id) {
    return document.getElementById(id);
  }

  function abrirModal(id) {
    const ventana = modal(id);
    if (!ventana) return;
    ventana.hidden = false;
    body.classList.add('modal-abierto');
    const foco = ventana.querySelector('input:not([type="hidden"]):not([readonly]), select, textarea');
    if (foco) foco.focus();
  }

  function cerrarModales() {
    document.querySelectorAll('.modal').forEach((ventana) => {
      ventana.hidden = true;
    });
    body.classList.remove('modal-abierto');
  }

  function vaciar(form) {
    form.querySelectorAll('input, textarea, select').forEach((campo) => {
      if (campo.name === 'csrf') return;
      if (campo.name === 'id') {
        campo.value = '';
        return;
      }
      if (campo.tagName === 'SELECT') {
        campo.selectedIndex = 0;
        return;
      }
      campo.value = '';
    });
  }

  function llenar(form, datos) {
    Object.keys(datos).forEach((clave) => {
      const campo = form.querySelector('[name="' + clave + '"]');
      if (!campo || campo.type === 'file' || campo.name === 'csrf') return;
      campo.value = datos[clave] ?? '';
    });
  }

  function preparar(form, modo) {
    const enviar = form.querySelector('[type="submit"]');
    const generar = form.querySelector('[data-generar-modulos]');
    if (generar) generar.disabled = modo === 'ver';
    form.querySelectorAll('input, textarea, select').forEach((campo) => {
      if (campo.name === 'csrf') return;
      campo.disabled = modo === 'ver';
      if (modo !== 'ver' && campo.name !== 'codigo_curso') campo.readOnly = false;
    });
    if (enviar) enviar.hidden = modo === 'ver';
    const iniciales = form.querySelector('[name="iniciales"]');
    const codigo = form.querySelector('[name="codigo_curso"]');
    const clave = form.querySelector('[name="clave"]');
    const confirmar = form.querySelector('[name="clave_confirmacion"]');
    form.dataset.modo = modo;
    if (modo === 'nuevo') {
      vaciar(form);
      if (codigo) codigo.placeholder = 'Se asigna al guardar';
      if (clave) clave.required = true;
      if (confirmar) confirmar.required = true;
      if (form._reiniciarModulos) form._reiniciarModulos();
    }
    if (modo === 'editar') {
      if (iniciales) iniciales.readOnly = true;
      if (codigo) codigo.readOnly = true;
      if (clave) clave.required = false;
      if (confirmar) confirmar.required = false;
    }
    if (modo === 'ver') {
      if (iniciales) iniciales.readOnly = true;
      if (codigo) codigo.readOnly = true;
    }
  }

  document.querySelectorAll('[data-abrir]').forEach((boton) => {
    boton.addEventListener('click', () => {
      const id = boton.dataset.abrir;
      const ventana = modal(id);
      if (!ventana) return;
      const form = ventana.querySelector('form');
      const titulo = ventana.querySelector('.modal-titulo');
      if (titulo && boton.dataset.titulo) titulo.textContent = boton.dataset.titulo;
      if (!form) {
        abrirModal(id);
        return;
      }
      let datos = null;
      if (boton.dataset.editar) {
        try {
          datos = JSON.parse(boton.dataset.editar);
        } catch (error) {
          datos = null;
        }
      }
      if (boton.dataset.ver === '1') {
        preparar(form, 'ver');
        if (datos) llenar(form, datos);
        if (form._pintarModulos) form._pintarModulos(datos && Array.isArray(datos.modulos) ? datos.modulos : []);
      } else if (datos) {
        preparar(form, 'editar');
        llenar(form, datos);
        if (form._pintarModulos) form._pintarModulos(Array.isArray(datos.modulos) ? datos.modulos : []);
      } else if (boton.dataset.limpiar === '1') {
        preparar(form, 'nuevo');
      }
      abrirModal(id);
    });
  });

  document.querySelectorAll('[data-cerrar]').forEach((boton) => {
    boton.addEventListener('click', cerrarModales);
  });
  document.querySelectorAll('.modal').forEach((ventana) => {
    ventana.addEventListener('click', (evento) => {
      if (evento.target === ventana) cerrarModales();
    });
  });
  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') {
      cerrarModales();
      body.classList.remove('menu-abierto');
    }
  });

  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (evento) => {
      if (form.dataset.confirmar && !window.confirm(form.dataset.confirmar)) {
        evento.preventDefault();
        return;
      }
      const boton = form.querySelector('[type="submit"]');
      if (boton) boton.disabled = true;
    });
  });

  document.querySelectorAll('select[data-enviar]').forEach((selector) => {
    selector.addEventListener('change', () => selector.form.submit());
  });

  document.querySelectorAll('form[data-modulos-curso]').forEach((form) => {
    const cantidad = form.querySelector('[name="cantidad_modulos"]');
    const lista = form.querySelector('[data-lista-modulos]');
    const generar = form.querySelector('[data-generar-modulos]');
    if (!cantidad || !lista) return;

    function leer() {
      return [...lista.querySelectorAll('[data-modulo]')].map((bloque) => ({
        titulo: bloque.querySelector('[name="modulos_titulo[]"]')?.value || '',
        contenido: bloque.querySelector('[name="modulos_contenido[]"]')?.value || '',
      }));
    }

    function pintar(items) {
      const deshabilitado = form.dataset.modo === 'ver';
      lista.replaceChildren();
      items.forEach((item, indice) => {
        const bloque = document.createElement('fieldset');
        bloque.className = 'modulo-editor';
        bloque.dataset.modulo = '1';

        const leyenda = document.createElement('legend');
        leyenda.textContent = 'Módulo ' + (indice + 1);
        bloque.append(leyenda);

        const campoTitulo = document.createElement('label');
        campoTitulo.className = 'campo';
        const etiquetaTitulo = document.createElement('span');
        etiquetaTitulo.textContent = 'Título';
        const input = document.createElement('input');
        input.name = 'modulos_titulo[]';
        input.required = true;
        input.maxLength = 180;
        input.value = item.titulo || '';
        campoTitulo.append(etiquetaTitulo, input);

        const campoContenido = document.createElement('label');
        campoContenido.className = 'campo';
        const etiquetaContenido = document.createElement('span');
        etiquetaContenido.textContent = 'Contenido';
        const area = document.createElement('textarea');
        area.name = 'modulos_contenido[]';
        area.maxLength = 3000;
        area.rows = 4;
        area.value = item.contenido || '';
        campoContenido.append(etiquetaContenido, area);

        if (deshabilitado) {
          input.disabled = true;
          area.disabled = true;
        }
        bloque.append(campoTitulo, campoContenido);
        lista.append(bloque);
      });
    }

    function aplicar(conservar) {
      const n = Math.max(0, Math.min(12, parseInt(cantidad.value || '0', 10) || 0));
      if (cantidad.value !== '') cantidad.value = String(n);
      const previos = conservar ? leer() : [];
      const items = [];
      for (let i = 0; i < n; i += 1) items.push(previos[i] || { titulo: '', contenido: '' });
      pintar(items);
    }

    form._pintarModulos = (items) => {
      const listaModulos = Array.isArray(items) ? items.slice(0, 12) : [];
      cantidad.value = String(listaModulos.length);
      pintar(listaModulos);
    };
    form._reiniciarModulos = () => {
      cantidad.value = '';
      pintar([]);
    };
    cantidad.addEventListener('change', () => aplicar(true));
    if (generar) generar.addEventListener('click', () => aplicar(true));
  });

  if (body.dataset.abrirModal) abrirModal(body.dataset.abrirModal);
})();
