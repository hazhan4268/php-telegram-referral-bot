const express = require('express');
const fs = require('fs');
const app = express();
app.use(express.json());

app.post('/webhook', (req, res) => {
  const body = JSON.stringify(req.body);
  fs.appendFileSync(__dirname + '/node_webhook.log', new Date().toISOString() + ' ' + body + '\n');
  // Minimal security: check header X-Telegram-Bot-Api-Secret-Token if provided
  // TODO: validate against config or env
  res.send('ok');
});

const port = process.env.PORT || 3000;
app.listen(port, () => console.log('Node adapter listening on port', port));
