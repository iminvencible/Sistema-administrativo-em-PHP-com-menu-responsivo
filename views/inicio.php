<div class="section-heading"><div><p class="eyebrow">VISÃO GERAL</p><h2>Pronto para o próximo atendimento</h2></div><span class="badge">Hoje, <?= h(date('d/m/Y')) ?></span></div>
<div class="stats-grid">
    <a href="index.php?opcao=cliente" class="stat"><span>Clientes cadastrados</span><strong><?= h($data['stats']['clientes']) ?></strong><small>Pessoas e veículos</small></a>
    <a href="index.php?opcao=produto" class="stat"><span>Peças no catálogo</span><strong><?= h($data['stats']['produtos']) ?></strong><small>Produtos para motos</small></a>
    <a href="index.php?opcao=estoque" class="stat stat-alert"><span>Peças abaixo do mínimo</span><strong><?= h($data['stats']['criticos']) ?></strong><small>Confira a reposição</small></a>
    <a href="index.php?opcao=vendas" class="stat"><span>Ordens em aberto</span><strong><?= h($data['stats']['ordens']) ?></strong><small>Atendimentos em andamento</small></a>
</div>
<div class="welcome-note"><div><p class="eyebrow">ROTINA DA OFICINA</p><h3>Um atendimento começa com um bom cadastro.</h3><p>Registre o cliente e sua moto, abra uma ordem de serviço e conclua a venda. O estoque acompanha cada peça utilizada.</p></div><a class="button" href="index.php?opcao=cliente">Cadastrar cliente <span aria-hidden="true">→</span></a></div>
