from flask import Flask, request
import datetime

app = Flask(__name__)

@app.route('/webhook', methods=['POST'])
def webhook():
    data = request.get_json(force=True)
    with open('python_webhook.log', 'a', encoding='utf-8') as f:
        f.write(datetime.datetime.utcnow().isoformat() + ' ' + str(data) + '\n')
    return 'ok'

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
