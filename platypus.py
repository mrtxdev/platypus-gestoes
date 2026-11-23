
import socket
import re
import sys
import os
from pathlib import Path


CIANO = "\033[96m"
VERDE = "\033[92m"
AMARELO = "\033[93m"
VERMELHO = "\033[91m"
AZUL = "\033[94m"
BRANCO = "\033[97m"
CINZA = "\033[90m"
RESET = "\033[0m"
NEGRITO = "\033[1m"



banner = r"""
           /$$             /$$                                            
          | $$            | $$                                            
  /$$$$$$ | $$  /$$$$$$  /$$$$$$   /$$   /$$  /$$$$$$  /$$   /$$  /$$$$$$$
 /$$__  $$| $$ |____  $$|_  $$_/  | $$  | $$ /$$__  $$| $$  | $$ /$$_____/
| $$  \ $$| $$  /$$$$$$$  | $$    | $$  | $$| $$  \ $$| $$  | $$|  $$$$$$ 
| $$  | $$| $$ /$$__  $$  | $$ /$$| $$  | $$| $$  | $$| $$  | $$ \____  $$
| $$$$$$$/| $$|  $$$$$$$  |  $$$$/|  $$$$$$$| $$$$$$$/|  $$$$$$/ /$$$$$$$/
| $$____/ |__/ \_______/   \___/   \____  $$| $$____/  \______/ |_______/ 
| $$                               /$$  | $$| $$                          
| $$                              |  $$$$$$/| $$                          
|__/                               \______/ |__/  
"""


ARQUIVO_ENV = Path(__file__).parent / ".env"


# OBTER IP LOCAL


def obter_ip_atual():

    conexao = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    try:
        conexao.connect(("8.8.8.8", 80))
        return conexao.getsockname()[0]

    finally:
        conexao.close()



def obter_url():

    if not ARQUIVO_ENV.exists():
        return "Arquivo .env nao encontrado"

    with open(ARQUIVO_ENV, "r", encoding="utf-8") as arquivo:
        conteudo = arquivo.read()

    resultado = re.search(
        r"(?m)^APP_BASE_URL\s*=\s*(.*)$",
        conteudo
    )

    if resultado:
        return resultado.group(1).strip()

    return "APP_BASE_URL nao encontrada"




def atualizar_env():

    if not ARQUIVO_ENV.exists():
        raise FileNotFoundError("Arquivo .env nao encontrado!")

    ip = obter_ip_atual()

    with open(ARQUIVO_ENV, "r", encoding="utf-8", newline="") as arquivo:
        conteudo = arquivo.read()

    padrao = r"(?m)^(APP_BASE_URL\s*=\s*https?://)([^:/\s]+)(:\d+)?(.*)$"

    novo_conteudo, quantidade = re.subn(
        padrao,
        lambda m: m.group(1) + ip + (m.group(3) or "") + m.group(4),
        conteudo,
        count=1
    )

    if quantidade == 0:
        raise ValueError("APP_BASE_URL nao encontrada ou invalida!")

    with open(ARQUIVO_ENV, "w", encoding="utf-8", newline="") as arquivo:
        arquivo.write(novo_conteudo)

    return ip




def limpar():

    os.system("cls" if os.name == "nt" else "clear")



def painel():

    limpar()

    print(f"{CIANO}{NEGRITO}{banner}{RESET}")

    print(f"{AZUL}{'=' * 55}{RESET}")
    print(f"{BRANCO}{NEGRITO}              <<PAINEL MANAGER>> {RESET}")
    print(f"{AZUL}{'=' * 55}{RESET}")

    print()
    print(f"{CINZA} IMFORMAÇÃO DE REDE{RESET}")
    print()

    try:
        ip = obter_ip_atual()
        print(f" {VERDE}[ONLINE]{RESET} Conexao de rede detectada")
        print(f" {CIANO}IP LOCAL:{RESET} {BRANCO}{ip}{RESET}")

    except OSError:
        ip = None
        print(f" {VERMELHO}[OFFLINE]{RESET} Nao foi possivel obter o IP")

    print(f" {CIANO}ARQUIVO:{RESET} {BRANCO}.env{RESET}")
    print(f" {CIANO}STATUS:{RESET} {AMARELO}Sistema em desenvolvimento{RESET}")

    print()
    print(f"{AZUL}{'-' * 55}{RESET}")

    print(f" {BRANCO}{NEGRITO}MENU DE OPERACOES{RESET}")
    print()

    print(f" {VERDE}[1]{RESET} Atualizar IP no arquivo .env")
    print(f" {CIANO}[2]{RESET} Consultar URLs atual")
    print(f" {AMARELO}[3]{RESET} Verificar conexao de rede")
    print(f" {VERMELHO}[0]{RESET} Sair do programa")

    print()
    print(f"{AZUL}{'-' * 55}{RESET}")

    return ip



def executar():

    while True:

        ip = painel()

        print()

        opcao = input(f" {CIANO}]--{RESET}{BRANCO}>>>{RESET} ")

        # ATUALIZAR IP

        if opcao == "1":

            limpar()

            print(f"{CIANO}{NEGRITO}ATUALIZANDO CONFIGURACOES...{RESET}")
            print()

            try:

                ip = atualizar_env()

                print(f"{VERDE}[SUCESSO]{RESET} IP atualizado")
                print()
                print(f" IP: {BRANCO}{ip}{RESET}")
                print(f" Arquivo: {CIANO}.env{RESET}")

                print()
                print(f"{VERDE}APP_BASE_URL atualizada com sucesso!{RESET}")

            except Exception as erro:

                print(f"{VERMELHO}[ERRO] {erro}{RESET}")

            input(f"\n{CINZA}Pressione ENTER para continuar...{RESET}")

        # CONSULTAR CONFIGURACAO

        elif opcao == "2":

            limpar()

            print(f"{CIANO}{NEGRITO}CONFIGURACAO ATUAL{RESET}")
            print()

            print(f" URL: {VERDE}{obter_url()}{RESET}")
            print(f" URL: {AZUL}{obter_url()}/login{RESET}")
            print(f" URL: {AZUL}{obter_url()}/novo-usuario{RESET}")

            print()
            input("Pressione ENTER para continuar...")

        # VERIFICAR REDE

        elif opcao == "3":

            limpar()

            print(f"{CIANO}{NEGRITO}VERIFICACAO DE REDE{RESET}")
            print()

            try:

                ip = obter_ip_atual()

                print(f"{VERDE}[OK] Rede detectada{RESET}")
                print(f" IPv4: {BRANCO}{ip}{RESET}")

            except OSError:

                print(f"{VERMELHO}[ERRO] Sem conexao de rede{RESET}")

            print()
            input("Pressione ENTER para continuar...")

        # SAIR

        elif opcao == "0":

            limpar()

            print(f"{CIANO}{NEGRITO}{banner}{RESET}")

            print(f"{VERDE}Encerrando painel...{RESET}")

            break

        else:

            print(f"\n{VERMELHO}[ERRO] Opcao invalida!{RESET}")
            input("Pressione ENTER para continuar...")




if __name__ == "__main__":

    try:
        executar()

    except KeyboardInterrupt:
        print(f"\n{AMARELO}Programa encerrado pelo usuario.{RESET}")
        sys.exit(0)
