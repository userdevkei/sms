curl -X POST https://demo.freightmax.co/webhooks/banks/equity \
  -u "test:tset" \
  -H "Content-Type: application/json" \
  -d '{
    "callbackType": "IPN",
    "customer": {
      "name": "Test Parent",
      "mobileNumber": "254712345678",
      "reference": "071648816466242"
    },
    "transaction": {
      "date": "2026-09-17 17:15:20",
      "reference": "TEST-'"$(date +%s)"'",
      "paymentMode": "MPESA",
      "amount": 15000,
      "currency": "KES",
      "billNumber": "RAM03045",
      "servedBy": "EQ",
      "additionalInfo": "MPESA",
      "orderAmount": 15000,
      "serviceCharge": 50.25,
      "orderCurrency": "KES",
      "status": "SUCCESS",
      "remarks": "00:Approved"
    },
    "bank": {
      "reference": "TEST-'"$(date +%s)"'",
      "transactionType": "C",
      "account": null
    }
  }'
