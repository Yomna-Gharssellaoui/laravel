
# Python image
FROM python:3.11-slim

# Python environment
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1
ENV PIP_NO_CACHE_DIR=1

# Working directory
WORKDIR /app

# Install Python dependencies
COPY requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt

# Copy ML source code
COPY . .

# Train all 7 ML models during image build
RUN python train_all.py

# FastAPI port
EXPOSE 8001

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=120s --retries=3 \
    CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8001/health', timeout=3)" || exit 1

# Start FastAPI
CMD ["uvicorn", "service:app", "--host", "0.0.0.0", "--port", "8001"]
