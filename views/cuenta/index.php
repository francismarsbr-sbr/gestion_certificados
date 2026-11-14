<section class="encabezado-pagina">
    <div>
        <h1>Mi cuenta</h1>
        <p>Cambie su clave. La sesión actual se mantiene.</p>
    </div>
</section>
<form method="post" action="index.php?m=cuenta&amp;a=guardar" class="formulario panel panel-estrecho">
    <?= csrf_campo() ?>
    <label class="campo"><span>Clave actual</span><input type="password" name="clave_actual" required maxlength="72" autocomplete="current-password"></label>
    <label class="campo"><span>Clave nueva</span><input type="password" name="clave_nueva" required minlength="8" maxlength="72" autocomplete="new-password"></label>
    <label class="campo"><span>Confirmar clave nueva</span><input type="password" name="clave_confirmacion" required minlength="8" maxlength="72" autocomplete="new-password"></label>
    <button type="submit" class="btn btn-pri">Actualizar clave</button>
</form>
