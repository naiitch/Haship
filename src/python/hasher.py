#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
HASHIP PROJECT - Motor Criptográfico de Integridad (SHA-256)
Autor: Nico (Lead Developer)
Versión: 1.1 (Sincronizado con Flujo PHP 2.0)
-------------------------------------------------------------------------
DESCRIPCIÓN:
Este módulo de bajo nivel es el encargado de generar el "ADN digital" de 
los documentos PDF subidos al sistema. Utiliza el estándar industrial 
SHA-256 para garantizar que cualquier modificación posterior al archivo, 
por mínima que sea, altere el hash resultante.

INTEROPERABILIDAD:
Invocado desde PHP (upload.php / verify_integrity.php) mediante shell_exec().
Recibe la ruta del archivo por sys.argv y devuelve ÚNICAMENTE el hash 
limpio por stdout para evitar colisiones en la base de datos.
-------------------------------------------------------------------------
"""

import hashlib
import sys
import os

def generate_hash(file_path):
    """
    Calcula la huella SHA-256 de un archivo de forma eficiente.
    
    Args:
        file_path (str): Ruta absoluta o relativa al documento.
        
    Returns:
        str: Representación hexadecimal del hash (64 caracteres) o prefijo ERROR.
    """
    # Inicializamos el constructor del algoritmo SHA-256
    sha256_hash = hashlib.sha256()
    
    try:
        # Abrimos el archivo en modo 'rb' (Read Binary)
        # Esencial para tratar PDFs como secuencias de bytes y no como texto.
        with open(file_path, "rb") as f:
            # OPTIMIZACIÓN DE MEMORIA (Chunking):
            # Leemos el archivo en bloques de 4096 bytes (4KB).
            # Esto evita que archivos grandes colapsen la memoria RAM del servidor.
            for byte_block in iter(lambda: f.read(4096), b""):
                sha256_hash.update(byte_block)
        
        # Devolvemos el resultado en formato legible (hexadecimal)
        return sha256_hash.hexdigest()
        
    except FileNotFoundError:
        return "ERROR_FILE_NOT_FOUND"
    except PermissionError:
        return "ERROR_PERMISSION_DENIED"
    except Exception as e:
        return f"ERROR_SYSTEM_{str(e)}"

if __name__ == "__main__":
    # PUNTO DE ENTRADA (CLI):
    # El script espera recibir al menos un argumento desde el shell_exec de PHP.
    if len(sys.argv) > 1:
        file_to_check = sys.argv[1]
        
        # Validamos que la ruta sea un archivo real antes de procesar
        if os.path.isfile(file_to_check):
            resultado = generate_hash(file_to_check)
            
            # VALIDACIÓN DE SALIDA: 
            # Si el resultado es un hash válido (64 chars), lo imprimimos.
            # Si no, enviamos el error al flujo de error (stderr) para no ensuciar el hash.
            if len(resultado) == 64 and "ERROR" not in resultado:
                sys.stdout.write(resultado)
            else:
                sys.stderr.write(resultado)
                sys.exit(1)
        else:
            sys.stderr.write("ERROR_INVALID_PATH")
            sys.exit(1)
    else:
        # Si se ejecuta sin argumentos, devolvemos error de uso
        sys.stderr.write("ERROR_NO_ARGUMENTS_PROVIDED")
        sys.exit(1)