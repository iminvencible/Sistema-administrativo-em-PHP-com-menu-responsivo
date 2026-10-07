"""Testes HTTP com MySQL real. Execute somente em banco descartável."""
import hashlib
import http.cookiejar
import os
from pathlib import Path
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:8000')
jar = http.cookiejar.CookieJar()
snapshots = {}


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, msg, headers, newurl):
        return None


opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), NoRedirect())


def request(route='', fields=None):
    encoded_fields = {key + '[]' if isinstance(value, list) else key: value for key, value in (fields or {}).items()}
    data = urllib.parse.urlencode(encoded_fields, doseq=True).encode() if fields is not None else None
    try:
        response = opener.open(BASE + '/' + route, data=data, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    return response.code, response.read().decode(), response.headers


def sql(query):
    result = subprocess.run([
        'mysql', '-h', os.environ.get('DB_HOST', '127.0.0.1'),
        '-P', os.environ.get('DB_PORT', '3306'), '-u', os.environ.get('DB_USER', 'root'),
        '--batch', '--skip-column-names', os.environ.get('DB_NAME', 'oficina_motos'),
    ], input=query, text=True, capture_output=True, check=True)
    return result.stdout.strip()


def token(route):
    code, body, _ = request('index.php?opcao=' + route)
    assert code == 200, (route, code, body)
    match = re.search(r'name="csrf" value="([a-f0-9]{64})"', body)
    assert match, route
    return match.group(1)


def post(route, fields, expected=303):
    fields = dict(fields, csrf=token(route))
    code, body, headers = request('index.php?opcao=' + route, fields)
    assert code == expected, (route, code, body)
    return body, headers


for attempt in range(50):
    try:
        code, body, _ = request('index.php')
        if code == 200:
            break
    except urllib.error.URLError:
        pass
    time.sleep(.2)
else:
    raise AssertionError('Servidor PHP não iniciou com conexão MySQL.')

assert body.count('class="botao-menu ') == 6
assert request('index.php?opcao=../../config/database.local.php')[0] == 404
assert request('index.php?opcao[]=cliente')[0] == 200
for route in ['cliente', 'produto', 'vendas', 'estoque', 'configuracoes', 'sair']:
    assert request('index.php?opcao=' + route)[0] == 200, route
for letter, route in zip('ABCDEF', ['cliente', 'produto', 'vendas', 'estoque', 'configuracoes', 'sair']):
    code, _, headers = request('processa.php', {'opcao': letter})
    assert code == 303 and headers['Location'] == 'index.php?opcao=' + route
print('OK: seis módulos, rota inválida e compatibilidade A–F')

client = dict(acao='salvar', nome='Cliente <script>teste</script>', cpf='529.982.247-25',
              telefone='(11) 99999-9999', email='teste@example.com', modelo_moto='Honda CG 160', placa_moto='ABC1D23')
post('cliente', dict(client, cpf='00000000000'), 422)
code, _, _ = request('index.php?opcao=cliente', dict(client, csrf='invalido'))
assert code == 403 and sql('SELECT COUNT(*) FROM clientes') == '0'
post('cliente', client)
client_id = sql('SELECT id FROM clientes LIMIT 1')
post('cliente', client, 422)
body = request('index.php?opcao=cliente')[1]
assert '&lt;script&gt;teste&lt;/script&gt;' in body and '<script>teste</script>' not in body
post('cliente', dict(client, id=client_id, nome='Cliente Teste'))
client['nome'] = 'Cliente Teste'
print('OK: cadastro, edição, CPF, duplicidade, CSRF e escape HTML')

product = dict(acao='salvar', sku='TESTE-001', nome='Peça teste', categoria='Freios',
               compatibilidade='Honda CG 160', preco='12.34', quantidade='2', quantidade_minima='3')
post('produto', product)
product_id = sql("SELECT id FROM produtos WHERE sku='TESTE-001'")
post('produto', product, 422)
post('produto', dict(product, id=product_id, preco='20.25'))
assert sql('SELECT quantidade FROM estoque WHERE produto_id=' + product_id) == '2'
post('produto', dict(product, sku='TEMP-001', quantidade='0'))
temporary = sql("SELECT id FROM produtos WHERE sku='TEMP-001'")
post('produto', dict(acao='excluir', id=temporary))
assert sql('SELECT COUNT(*) FROM estoque WHERE produto_id=' + temporary) == '0'
post('cliente', dict(client, cpf='11144477735', nome='Temporário'))
temporary = sql("SELECT id FROM clientes WHERE cpf='11144477735'")
post('cliente', dict(acao='excluir', id=temporary))
print('OK: peças, edição, estoque inicial e exclusão sem histórico')

post('vendas', dict(acao='abrir_os', cliente_id=client_id, descricao='Revisão dos freios'))
order_id = sql('SELECT id FROM ordens_servico LIMIT 1')
post('vendas', dict(acao='iniciar_os', id=order_id))
assert sql('SELECT status FROM ordens_servico WHERE id=' + order_id) == 'em_andamento'
sale = dict(acao='vender', cliente_id=client_id, ordem_servico_id=order_id,
            produto_id=[product_id, product_id], quantidade=['1', '1'], servico='Revisão dos freios', mao_obra='30.10')
no_stock_id = sql("SELECT id FROM produtos WHERE sku='REL-001'")
post('vendas', dict(sale, produto_id=[product_id, no_stock_id], quantidade=['1', '1']), 422)
assert sql('SELECT COUNT(*) FROM vendas') == '0'
assert sql('SELECT quantidade FROM estoque WHERE produto_id=' + product_id) == '2'
post('vendas', sale)
sale_id = sql('SELECT id FROM vendas LIMIT 1')
assert sql('SELECT total FROM vendas WHERE id=' + sale_id) == '70.60'
assert sql('SELECT quantidade FROM estoque WHERE produto_id=' + product_id) == '0'
assert sql('SELECT status FROM ordens_servico WHERE id=' + order_id) == 'concluida'
assert sql('SELECT COUNT(*) FROM venda_itens WHERE venda_id=' + sale_id) == '1'
assert sql('SELECT quantidade FROM venda_itens WHERE venda_id=' + sale_id) == '2'
post('vendas', sale, 422)
assert sql('SELECT COUNT(*) FROM vendas') == '1'
post('cliente', dict(client, id=client_id, nome='Nome novo', modelo_moto='Yamaha Factor 150'))
post('produto', dict(product, id=product_id, preco='99.90'))
report = request('index.php?opcao=vendas')[1]
assert 'Cliente Teste' in report and 'Honda CG 160 / ABC1D23' in report and 'R$ 70,60' in report
assert 'R$ 20,25' in report
assert 'id="print-report"' in report
assert 'Nenhuma venda registrada neste período.' in request('index.php?opcao=vendas&inicio=2000-01-01&fim=2000-01-31')[1]
assert 'Informe uma data válida.' in request('index.php?opcao=vendas&inicio=2026-02-30')[1]
snapshots['vendas-claro.html'] = report
snapshots['clientes-claro.html'] = request('index.php?opcao=cliente')[1]
snapshots['inicio-claro.html'] = request('index.php')[1]
snapshots['produtos-claro.html'] = request('index.php?opcao=produto')[1]
post('cliente', dict(acao='excluir', id=client_id), 422)
post('produto', dict(acao='excluir', id=product_id), 422)
print('OK: OS, venda atômica, saldo insuficiente, histórico e relatório filtrado')

stock = dict(acao='movimentar', produto_id=product_id, tipo='entrada', quantidade='5', quantidade_minima='3', motivo='Reposição teste')
assert 'Reposição necessária' in request('index.php?opcao=estoque')[1]
snapshots['estoque-claro.html'] = request('index.php?opcao=estoque')[1]
post('estoque', stock)
post('estoque', dict(stock, tipo='saida', quantidade='6'), 422)
assert sql('SELECT quantidade FROM estoque WHERE produto_id=' + product_id) == '5'
post('estoque', dict(stock, tipo='saida', quantidade='2'))
assert sql('SELECT quantidade FROM estoque WHERE produto_id=' + product_id) == '3'
post('configuracoes', dict(acao='preferencias', tema='escuro', fonte_ampliada='1'))
body = request('index.php?opcao=cliente')[1]
assert 'data-tema="escuro"' in body and 'class="fonte-ampliada"' in body
assert sql('SELECT tema FROM configuracoes LIMIT 1') == 'escuro'
snapshots['clientes-escuro.html'] = body
snapshots['configuracoes-escuro.html'] = request('index.php?opcao=configuracoes')[1]
snapshots['sair-escuro.html'] = request('index.php?opcao=sair')[1]
post('configuracoes', dict(acao='preferencias', tema='inválido'), 422)
print('OK: entradas, saídas, limite de estoque, tema e acessibilidade persistidos')

old_session = next(cookie.value for cookie in jar if cookie.name == 'PHPSESSID')
old_hash = hashlib.sha256(old_session.encode()).hexdigest()
assert request('index.php?opcao=sair')[0] == 200
assert sql("SELECT encerrada_em IS NULL FROM sessoes WHERE chave_sessao='" + old_hash + "'") == '1'
csrf = token('sair')
code, _, _ = request('index.php?opcao=sair', dict(acao='sair', csrf='inválido'))
assert code == 403
code, _, headers = request('index.php?opcao=sair', dict(acao='sair', csrf=csrf))
assert code == 303 and headers['Location'] == 'index.php?opcao=encerrado'
assert 'PHPSESSID=deleted' in headers.get('Set-Cookie', '') or 'Max-Age=0' in headers.get('Set-Cookie', '')
assert sql("SELECT encerrada_em IS NOT NULL FROM sessoes WHERE chave_sessao='" + old_hash + "'") == '1'
assert 'Sessão encerrada com segurança' in request(headers['Location'])[1]
assert 'data-tema="claro"' in request('index.php')[1]
assert next(cookie.value for cookie in jar if cookie.name == 'PHPSESSID') != old_session
assert sql('SELECT COUNT(*) FROM vendas') == '1'
print('OK: confirmação, cookie removido, sessão destruída e dados preservados')
print('Todos os testes passaram.')
if os.environ.get('TEST_CAPTURE_UI') == '1':
    # HTML renderizado pelo PHP, com dados do banco descartável, para revisão visual.
    destination = Path(__file__).parent / 'results'
    destination.mkdir(exist_ok=True)
    for name, html in snapshots.items():
        (destination / name).write_text(html, encoding='utf-8')
