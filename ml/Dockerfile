# Local ML service: the models are trained during the build (about 30 s), no external API
FROM python:3.12-slim

WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .
RUN python train_all.py

EXPOSE 8001
HEALTHCHECK --interval=10s --retries=10 CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8001/health')"
CMD ["uvicorn", "service:app", "--host", "0.0.0.0", "--port", "8001"]
