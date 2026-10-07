<div class="section-heading"><div><p class="eyebrow">DO SEU JEITO</p><h2>Preferências da interface</h2></div><span class="badge">Salvas na sessão</span></div>
<form method="post" action="index.php?opcao=configuracoes" class="preferences-form">
    <?php csrf_input(); ?><input type="hidden" name="acao" value="preferencias">
    <fieldset><legend>Aparência</legend><p class="help">Escolha o tema que deixa a leitura mais confortável.</p><div class="theme-options">
        <label class="theme-option"><input type="radio" name="tema" value="claro" <?= $theme === 'claro' ? 'checked' : '' ?> required><span><strong>Modo Claro</strong><small>Superfícies claras e texto escuro</small></span><span class="theme-swatch swatch-light" aria-hidden="true">Aa</span></label>
        <label class="theme-option"><input type="radio" name="tema" value="escuro" <?= $theme === 'escuro' ? 'checked' : '' ?>><span><strong>Modo Escuro</strong><small>Superfícies escuras e texto claro</small></span><span class="theme-swatch swatch-dark" aria-hidden="true">Aa</span></label>
    </div></fieldset>
    <label class="toggle-row"><span><strong>Fonte ampliada</strong><small>Aumenta o tamanho dos textos em toda a interface.</small></span><input type="checkbox" name="fonte_ampliada" value="1" role="switch" <?= $large ? 'checked' : '' ?>></label>
    <div class="form-actions"><button type="submit">Salvar preferências</button><span class="help">As preferências continuam ativas ao navegar e recarregar.</span></div>
</form>
<p class="accessibility-note">Use Tab para navegar e Enter para acionar os controles. A interface respeita a preferência de reduzir movimentos do seu dispositivo.</p>
