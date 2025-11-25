@echo off
REM ============================================
REM Remove DEFINER Clauses from SQL Dump
REM ============================================
REM Double-click this file and drag your SQL file when prompted

powershell.exe -ExecutionPolicy Bypass -File "%~dp0remove_definers.ps1"
