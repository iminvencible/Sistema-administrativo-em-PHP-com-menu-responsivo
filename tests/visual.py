"""Captura HTML real dos testes com Chrome, sem dependências da aplicação."""
import base64
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parent.parent
results = root / 'tests' / 'results'
chrome = shutil.which('google-chrome') or shutil.which('chromium')
if not chrome:
    raise SystemExit('Chrome não está disponível para revisão visual.')
for asset in ('style.css', 'app.js'):
    shutil.copyfile(root / asset, results / asset)
cases = [
    ('inicio-claro.html', 1440, 1000),
    ('clientes-claro.html', 390, 1900),
    ('produtos-claro.html', 1440, 1400),
    ('vendas-claro.html', 1440, 2300),
    ('estoque-claro.html', 1440, 1500),
    ('configuracoes-escuro.html', 1440, 1300),
    ('sair-escuro.html', 390, 1700),
]
for name, width, height in cases:
    output = results / name.replace('.html', '.png')
    with tempfile.TemporaryDirectory() as profile:
        command = [chrome, '--headless', '--no-sandbox', '--disable-gpu',
                   '--allow-file-access-from-files', '--hide-scrollbars',
                   '--user-data-dir=' + profile, '--virtual-time-budget=1000',
                   '--window-size=' + str(width) + ',' + str(height),
                   '--screenshot=' + str(output), (results / name).as_uri()]
        subprocess.run(command, check=True, capture_output=True, timeout=30)
    print('UI_IMAGE:' + output.name + ':' + base64.b64encode(output.read_bytes()).decode())

# Renderizar uma impressão real, com o CSS @media print.
pdf = results / 'vendas-impressao.pdf'
with tempfile.TemporaryDirectory() as profile:
    subprocess.run([chrome, '--headless', '--no-sandbox', '--disable-gpu',
                    '--allow-file-access-from-files', '--user-data-dir=' + profile,
                    '--virtual-time-budget=1000', '--no-pdf-header-footer',
                    '--print-to-pdf=' + str(pdf), (results / 'vendas-claro.html').as_uri()],
                   check=True, capture_output=True, timeout=30)
print('UI_PDF:' + base64.b64encode(pdf.read_bytes()).decode())
print('OK: capturas de desktop, mobile, tema escuro e impressão geradas.')
