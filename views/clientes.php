<?php $form = $error && raw($_POST, 'acao') === 'salvar' ? $_POST : $data['edit']; ?>
<div class="section-heading"><div><p class="eyebrow">PESSOAS &amp; VEÍCULOS</p><h2><?= raw($form, 'id') ? 'Editar cliente' : 'Novo cliente' ?></h2></div><span class="badge"><?= count($data['rows']) ?> cadastrados</span></div>
<form method="post" action="index.php?opcao=cliente" class="form-grid">
    <?php csrf_input(); ?><input type="hidden" name="acao" value="salvar"><input type="hidden" name="id" value="<?= h(raw($form, 'id')) ?>">
    <div class="field"><label for="nome">Nome completo</label><input id="nome" name="nome" maxlength="100" autocomplete="name" required value="<?= h(raw($form, 'nome')) ?>"></div>
    <div class="field"><label for="cpf">CPF</label><input id="cpf" name="cpf" maxlength="14" inputmode="numeric" placeholder="000.000.000-00" required value="<?= h(raw($form, 'cpf')) ?>"></div>
    <div class="field"><label for="telefone">Telefone com DDD</label><input id="telefone" name="telefone" type="tel" maxlength="20" autocomplete="tel" placeholder="(11) 99999-9999" required value="<?= h(raw($form, 'telefone')) ?>"></div>
    <div class="field"><label for="email">E-mail <span>(opcional)</span></label><input id="email" name="email" type="email" maxlength="120" autocomplete="email" value="<?= h(raw($form, 'email')) ?>"></div>
    <div class="field"><label for="modelo_moto">Modelo da moto</label><input id="modelo_moto" name="modelo_moto" maxlength="80" placeholder="Ex.: Honda CG 160" required value="<?= h(raw($form, 'modelo_moto')) ?>"></div>
    <div class="field"><label for="placa_moto">Placa da moto</label><input id="placa_moto" name="placa_moto" maxlength="8" pattern="[A-Za-z]{3}-?[0-9][A-Za-z0-9][0-9]{2}" placeholder="ABC1D23" required value="<?= h(raw($form, 'placa_moto')) ?>"></div>
    <div class="form-actions"><button type="submit">Salvar cliente</button><?php if (raw($form, 'id')): ?><a class="button secondary" href="index.php?opcao=cliente">Cancelar edição</a><?php endif; ?><span class="help">CPF, modelo e placa vinculam o atendimento à moto.</span></div>
</form>
<div class="section-heading divided"><h2>Clientes da oficina</h2></div>
<?php if (!$data['rows']): ?><p class="empty-state">Nenhum cliente cadastrado. Comece pelo formulário acima.</p><?php else: ?>
<div class="table-wrap" tabindex="0" role="region" aria-label="Clientes cadastrados"><table><thead><tr><th scope="col">Cliente / CPF</th><th scope="col">Contato</th><th scope="col">Moto / Placa</th><th scope="col">Ações</th></tr></thead><tbody>
<?php foreach ($data['rows'] as $row): ?><tr>
    <td><strong><?= h($row['nome']) ?></strong><small><?= h($row['cpf']) ?></small></td><td><?= h($row['telefone']) ?><small><?= h($row['email']) ?></small></td><td><?= h($row['modelo_moto']) ?><small><?= h($row['placa_moto']) ?></small></td>
    <td><div class="row-actions"><a class="text-action" href="index.php?opcao=cliente&amp;editar=<?= h($row['id']) ?>" aria-label="Editar <?= h($row['nome']) ?>">Editar</a><form method="post" action="index.php?opcao=cliente" data-confirm="Excluir este cliente? Registros com histórico serão preservados."><?php csrf_input(); ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= h($row['id']) ?>"><button class="text-action danger" aria-label="Excluir <?= h($row['nome']) ?>">Excluir</button></form></div></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
