/*
 * Format Amounts
 * Add separators every three digits
 */

var FormatAmounts = function(num, sep) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, sep);
}

module.exports = FormatAmounts
