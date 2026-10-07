<div class="logout-panel" role="region" aria-labelledby="logout-title">
    <span class="logout-symbol" aria-hidden="true">↗</span><p class="eyebrow">ENCERRAMENTO DE SESSÃO</p><h2 id="logout-title">Deseja sair do sistema?</h2><p>Os cadastros e vendas já salvos permanecem no banco de dados. A sessão atual e suas preferências serão encerradas.</p>
    <?php if (!empty($data['session'][0]['iniciada_em'])): ?><p class="help">Sessão iniciada em <?= h(date('d/m/Y H:i', strtotime($data['session'][0]['iniciada_em']))) ?>.</p><?php endif; ?>
    <form method="post" action="index.php?opcao=sair" class="logout-actions"><?php csrf_input(); ?><input type="hidden" name="acao" value="sair"><a class="button secondary" href="index.php">Continuar na oficina</a><button type="submit">Sim, encerrar sessão</button></form>
</div>
