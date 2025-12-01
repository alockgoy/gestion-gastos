FROM python:3.11-slim

WORKDIR /app

# Instalar dependencias
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copiar código del bot
COPY . .

# Variables de entorno
ENV PYTHONUNBUFFERED=1

# Ejecutar bot
CMD ["python", "bot.py"]