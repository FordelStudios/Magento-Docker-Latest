#!/bin/bash

echo "🚀 Setting up Formula OTP Validation Module"
echo "=========================================="

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

echo "📦 Installing Twilio SDK..."
docker-compose exec php composer require twilio/sdk:^7.0

if [ $? -eq 0 ]; then
    echo "✅ Twilio SDK installed successfully"
else
    echo "❌ Failed to install Twilio SDK"
    exit 1
fi

echo "🔧 Enabling Formula OTP Validation module..."
docker-compose exec php php bin/magento module:enable Formula_OtpValidation

echo "⬆️  Running setup upgrade..."
docker-compose exec php php bin/magento setup:upgrade

echo "🔨 Compiling DI..."
docker-compose exec php php bin/magento setup:di:compile

echo "🧹 Flushing cache..."
docker-compose exec php php bin/magento cache:flush

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📋 Next Steps:"
echo "1. Go to Admin Panel: Stores → Configuration → Formula → OTP Validation"
echo "2. Configure your Twilio credentials:"
echo "   - Account SID"
echo "   - Auth Token"
echo "   - From Phone Number (with country code, e.g., +1234567890)"
echo "3. Enable OTP Validation"
echo ""
echo "🔗 API Endpoints available:"
echo "   - POST /rest/V1/customers/me/address/send-otp"
echo "   - POST /rest/V1/customers/me/address/verify-otp"
echo ""
echo "🧪 Test the module by updating a customer address with a new phone number!"