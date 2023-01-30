<?php

namespace App\Enums;

enum GeneralSettingFields:string {
    case AnnouncementsSms = 'announcements_sms';
    case AnnouncementsEmail = 'announcements_email';
    case ReportsSms = 'reports_sms';
    case ReportsEmail = 'reports_email';
    case LoginVerificationSms = 'login_verification_sms';
    case LoginVerificationEmail = 'login_verification_email';
    case TransactionsSms = 'transactions_sms';
    case TransactionsEmail = 'transactions_email';
    case TradesSms = 'trades_sms';
    case TradesEmail = 'trades_email';
}
