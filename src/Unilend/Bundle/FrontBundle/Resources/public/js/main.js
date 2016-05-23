(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['exports'], factory);
    } else if (typeof exports === 'object' && typeof exports.nodeName !== 'string') {
        // CommonJS
        factory(exports);
    } else {
        // Browser globals
        factory(root.IBAN = {});
    }
}(this, function(exports){

    // Array.prototype.map polyfill
    // code from https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Array/map
    if (!Array.prototype.map){
        Array.prototype.map = function(fun /*, thisArg */){
            "use strict";

            if (this === void 0 || this === null)
                throw new TypeError();

            var t = Object(this);
            var len = t.length >>> 0;
            if (typeof fun !== "function")
                throw new TypeError();

            var res = new Array(len);
            var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
            for (var i = 0; i < len; i++)
            {
                // NOTE: Absolute correctness would demand Object.defineProperty
                //       be used.  But this method is fairly new, and failure is
                //       possible only if Object.prototype or Array.prototype
                //       has a property |i| (very unlikely), so use a less-correct
                //       but more portable alternative.
                if (i in t)
                    res[i] = fun.call(thisArg, t[i], i, t);
            }

            return res;
        };
    }

    var A = 'A'.charCodeAt(0),
        Z = 'Z'.charCodeAt(0);

    /**
     * Prepare an IBAN for mod 97 computation by moving the first 4 chars to the end and transforming the letters to
     * numbers (A = 10, B = 11, ..., Z = 35), as specified in ISO13616.
     *
     * @param {string} iban the IBAN
     * @returns {string} the prepared IBAN
     */
    function iso13616Prepare(iban) {
        iban = iban.toUpperCase();
        iban = iban.substr(4) + iban.substr(0,4);

        return iban.split('').map(function(n){
            var code = n.charCodeAt(0);
            if (code >= A && code <= Z){
                // A = 10, B = 11, ... Z = 35
                return code - A + 10;
            } else {
                return n;
            }
        }).join('');
    }

    /**
     * Calculates the MOD 97 10 of the passed IBAN as specified in ISO7064.
     *
     * @param iban
     * @returns {number}
     */
    function iso7064Mod97_10(iban) {
        var remainder = iban,
            block;

        while (remainder.length > 2){
            block = remainder.slice(0, 9);
            remainder = parseInt(block, 10) % 97 + remainder.slice(block.length);
        }

        return parseInt(remainder, 10) % 97;
    }

    /**
     * Parse the BBAN structure used to configure each IBAN Specification and returns a matching regular expression.
     * A structure is composed of blocks of 3 characters (one letter and 2 digits). Each block represents
     * a logical group in the typical representation of the BBAN. For each group, the letter indicates which characters
     * are allowed in this group and the following 2-digits number tells the length of the group.
     *
     * @param {string} structure the structure to parse
     * @returns {RegExp}
     */
    function parseStructure(structure){
        // split in blocks of 3 chars
        var regex = structure.match(/(.{3})/g).map(function(block){

            // parse each structure block (1-char + 2-digits)
            var format,
                pattern = block.slice(0, 1),
                repeats = parseInt(block.slice(1), 10);

            switch (pattern){
                case "A": format = "0-9A-Za-z"; break;
                case "B": format = "0-9A-Z"; break;
                case "C": format = "A-Za-z"; break;
                case "F": format = "0-9"; break;
                case "L": format = "a-z"; break;
                case "U": format = "A-Z"; break;
                case "W": format = "0-9a-z"; break;
            }

            return '([' + format + ']{' + repeats + '})';
        });

        return new RegExp('^' + regex.join('') + '$');
    }

    /**
     * Create a new Specification for a valid IBAN number.
     *
     * @param countryCode the code of the country
     * @param length the length of the IBAN
     * @param structure the structure of the underlying BBAN (for validation and formatting)
     * @param example an example valid IBAN
     * @constructor
     */
    function Specification(countryCode, length, structure, example){

        this.countryCode = countryCode;
        this.length = length;
        this.structure = structure;
        this.example = example;
    }

    /**
     * Lazy-loaded regex (parse the structure and construct the regular expression the first time we need it for validation)
     */
    Specification.prototype._regex = function(){
        return this._cachedRegex || (this._cachedRegex = parseStructure(this.structure))
    };

    /**
     * Check if the passed iban is valid according to this specification.
     *
     * @param {String} iban the iban to validate
     * @returns {boolean} true if valid, false otherwise
     */
    Specification.prototype.isValid = function(iban){
        return this.length == iban.length
            && this.countryCode === iban.slice(0,2)
            && this._regex().test(iban.slice(4))
            && iso7064Mod97_10(iso13616Prepare(iban)) == 1;
    };

    /**
     * Convert the passed IBAN to a country-specific BBAN.
     *
     * @param iban the IBAN to convert
     * @param separator the separator to use between BBAN blocks
     * @returns {string} the BBAN
     */
    Specification.prototype.toBBAN = function(iban, separator) {
        return this._regex().exec(iban.slice(4)).slice(1).join(separator);
    };

    /**
     * Convert the passed BBAN to an IBAN for this country specification.
     * Please note that <i>"generation of the IBAN shall be the exclusive responsibility of the bank/branch servicing the account"</i>.
     * This method implements the preferred algorithm described in http://en.wikipedia.org/wiki/International_Bank_Account_Number#Generating_IBAN_check_digits
     *
     * @param bban the BBAN to convert to IBAN
     * @returns {string} the IBAN
     */
    Specification.prototype.fromBBAN = function(bban) {
        if (!this.isValidBBAN(bban)){
            throw new Error('Invalid BBAN');
        }

        var remainder = iso7064Mod97_10(iso13616Prepare(this.countryCode + '00' + bban)),
            checkDigit = ('0' + (98 - remainder)).slice(-2);

        return this.countryCode + checkDigit + bban;
    };

    /**
     * Check of the passed BBAN is valid.
     * This function only checks the format of the BBAN (length and matching the letetr/number specs) but does not
     * verify the check digit.
     *
     * @param bban the BBAN to validate
     * @returns {boolean} true if the passed bban is a valid BBAN according to this specification, false otherwise
     */
    Specification.prototype.isValidBBAN = function(bban) {
        return this.length - 4 == bban.length
            && this._regex().test(bban);
    };

    var countries = {};

    function addSpecification(IBAN){
        countries[IBAN.countryCode] = IBAN;
    }

    addSpecification(new Specification("AD", 24, "F04F04A12",          "AD1200012030200359100100"));
    addSpecification(new Specification("AE", 23, "F03F16",             "AE070331234567890123456"));
    addSpecification(new Specification("AL", 28, "F08A16",             "AL47212110090000000235698741"));
    addSpecification(new Specification("AT", 20, "F05F11",             "AT611904300234573201"));
    addSpecification(new Specification("AZ", 28, "U04A20",             "AZ21NABZ00000000137010001944"));
    addSpecification(new Specification("BA", 20, "F03F03F08F02",       "BA391290079401028494"));
    addSpecification(new Specification("BE", 16, "F03F07F02",          "BE68539007547034"));
    addSpecification(new Specification("BG", 22, "U04F04F02A08",       "BG80BNBG96611020345678"));
    addSpecification(new Specification("BH", 22, "U04A14",             "BH67BMAG00001299123456"));
    addSpecification(new Specification("BR", 29, "F08F05F10U01A01",    "BR9700360305000010009795493P1"));
    addSpecification(new Specification("CH", 21, "F05A12",             "CH9300762011623852957"));
    addSpecification(new Specification("CR", 21, "F03F14",             "CR0515202001026284066"));
    addSpecification(new Specification("CY", 28, "F03F05A16",          "CY17002001280000001200527600"));
    addSpecification(new Specification("CZ", 24, "F04F06F10",          "CZ6508000000192000145399"));
    addSpecification(new Specification("DE", 22, "F08F10",             "DE89370400440532013000"));
    addSpecification(new Specification("DK", 18, "F04F09F01",          "DK5000400440116243"));
    addSpecification(new Specification("DO", 28, "U04F20",             "DO28BAGR00000001212453611324"));
    addSpecification(new Specification("EE", 20, "F02F02F11F01",       "EE382200221020145685"));
    addSpecification(new Specification("ES", 24, "F04F04F01F01F10",    "ES9121000418450200051332"));
    addSpecification(new Specification("FI", 18, "F06F07F01",          "FI2112345600000785"));
    addSpecification(new Specification("FO", 18, "F04F09F01",          "FO6264600001631634"));
    addSpecification(new Specification("FR", 27, "F05F05A11F02",       "FR1420041010050500013M02606"));
    addSpecification(new Specification("GB", 22, "U04F06F08",          "GB29NWBK60161331926819"));
    addSpecification(new Specification("GE", 22, "U02F16",             "GE29NB0000000101904917"));
    addSpecification(new Specification("GI", 23, "U04A15",             "GI75NWBK000000007099453"));
    addSpecification(new Specification("GL", 18, "F04F09F01",          "GL8964710001000206"));
    addSpecification(new Specification("GR", 27, "F03F04A16",          "GR1601101250000000012300695"));
    addSpecification(new Specification("GT", 28, "A04A20",             "GT82TRAJ01020000001210029690"));
    addSpecification(new Specification("HR", 21, "F07F10",             "HR1210010051863000160"));
    addSpecification(new Specification("HU", 28, "F03F04F01F15F01",    "HU42117730161111101800000000"));
    addSpecification(new Specification("IE", 22, "U04F06F08",          "IE29AIBK93115212345678"));
    addSpecification(new Specification("IL", 23, "F03F03F13",          "IL620108000000099999999"));
    addSpecification(new Specification("IS", 26, "F04F02F06F10",       "IS140159260076545510730339"));
    addSpecification(new Specification("IT", 27, "U01F05F05A12",       "IT60X0542811101000000123456"));
    addSpecification(new Specification("KW", 30, "U04A22",             "KW81CBKU0000000000001234560101"));
    addSpecification(new Specification("KZ", 20, "F03A13",             "KZ86125KZT5004100100"));
    addSpecification(new Specification("LB", 28, "F04A20",             "LB62099900000001001901229114"));
    addSpecification(new Specification("LC", 32, "U04F24",             "LC07HEMM000100010012001200013015"));
    addSpecification(new Specification("LI", 21, "F05A12",             "LI21088100002324013AA"));
    addSpecification(new Specification("LT", 20, "F05F11",             "LT121000011101001000"));
    addSpecification(new Specification("LU", 20, "F03A13",             "LU280019400644750000"));
    addSpecification(new Specification("LV", 21, "U04A13",             "LV80BANK0000435195001"));
    addSpecification(new Specification("MC", 27, "F05F05A11F02",       "MC5811222000010123456789030"));
    addSpecification(new Specification("MD", 24, "U02A18",             "MD24AG000225100013104168"));
    addSpecification(new Specification("ME", 22, "F03F13F02",          "ME25505000012345678951"));
    addSpecification(new Specification("MK", 19, "F03A10F02",          "MK07250120000058984"));
    addSpecification(new Specification("MR", 27, "F05F05F11F02",       "MR1300020001010000123456753"));
    addSpecification(new Specification("MT", 31, "U04F05A18",          "MT84MALT011000012345MTLCAST001S"));
    addSpecification(new Specification("MU", 30, "U04F02F02F12F03U03", "MU17BOMM0101101030300200000MUR"));
    addSpecification(new Specification("NL", 18, "U04F10",             "NL91ABNA0417164300"));
    addSpecification(new Specification("NO", 15, "F04F06F01",          "NO9386011117947"));
    addSpecification(new Specification("PK", 24, "U04A16",             "PK36SCBL0000001123456702"));
    addSpecification(new Specification("PL", 28, "F08F16",             "PL61109010140000071219812874"));
    addSpecification(new Specification("PS", 29, "U04A21",             "PS92PALS000000000400123456702"));
    addSpecification(new Specification("PT", 25, "F04F04F11F02",       "PT50000201231234567890154"));
    addSpecification(new Specification("RO", 24, "U04A16",             "RO49AAAA1B31007593840000"));
    addSpecification(new Specification("RS", 22, "F03F13F02",          "RS35260005601001611379"));
    addSpecification(new Specification("SA", 24, "F02A18",             "SA0380000000608010167519"));
    addSpecification(new Specification("SE", 24, "F03F16F01",          "SE4550000000058398257466"));
    addSpecification(new Specification("SI", 19, "F05F08F02",          "SI56263300012039086"));
    addSpecification(new Specification("SK", 24, "F04F06F10",          "SK3112000000198742637541"));
    addSpecification(new Specification("SM", 27, "U01F05F05A12",       "SM86U0322509800000000270100"));
    addSpecification(new Specification("ST", 25, "F08F11F02",          "ST68000100010051845310112"));
    addSpecification(new Specification("TL", 23, "F03F14F02",          "TL380080012345678910157"));
    addSpecification(new Specification("TN", 24, "F02F03F13F02",       "TN5910006035183598478831"));
    addSpecification(new Specification("TR", 26, "F05F01A16",          "TR330006100519786457841326"));
    addSpecification(new Specification("VG", 24, "U04F16",             "VG96VPVG0000012345678901"));
    addSpecification(new Specification("XK", 20, "F04F10F02",          "XK051212012345678906"));

    // Angola
    addSpecification(new Specification("AO", 25, "F21",                "AO69123456789012345678901"));
    // Burkina
    addSpecification(new Specification("BF", 27, "F23",                "BF2312345678901234567890123"));
    // Burundi
    addSpecification(new Specification("BI", 16, "F12",                "BI41123456789012"));
    // Benin
    addSpecification(new Specification("BJ", 28, "F24",                "BJ39123456789012345678901234"));
    // Ivory
    addSpecification(new Specification("CI", 28, "U01F23",             "CI17A12345678901234567890123"));
    // Cameron
    addSpecification(new Specification("CM", 27, "F23",                "CM9012345678901234567890123"));
    // Cape Verde
    addSpecification(new Specification("CV", 25, "F21",                "CV30123456789012345678901"));
    // Algeria
    addSpecification(new Specification("DZ", 24, "F20",                "DZ8612345678901234567890"));
    // Iran
    addSpecification(new Specification("IR", 26, "F22",                "IR861234568790123456789012"));
    // Jordan
    addSpecification(new Specification("JO", 30, "A04F22",             "JO15AAAA1234567890123456789012"));
    // Madagascar
    addSpecification(new Specification("MG", 27, "F23",                "MG1812345678901234567890123"));
    // Mali
    addSpecification(new Specification("ML", 28, "U01F23",             "ML15A12345678901234567890123"));
    // Mozambique
    addSpecification(new Specification("MZ", 25, "F21",                "MZ25123456789012345678901"));
    // Quatar
    addSpecification(new Specification("QA", 29, "U04A21",             "QA30AAAA123456789012345678901"));
    // Senegal
    addSpecification(new Specification("SN", 28, "U01F23",             "SN52A12345678901234567890123"));
    // Ukraine
    addSpecification(new Specification("UA", 29, "F25",                "UA511234567890123456789012345"));

    var NON_ALPHANUM = /[^a-zA-Z0-9]/g,
        EVERY_FOUR_CHARS =/(.{4})(?!$)/g;

    /**
     * Utility function to check if a variable is a String.
     *
     * @param v
     * @returns {boolean} true if the passed variable is a String, false otherwise.
     */
    function isString(v){
        return (typeof v == 'string' || v instanceof String);
    }

    /**
     * Check if an IBAN is valid.
     *
     * @param {String} iban the IBAN to validate.
     * @returns {boolean} true if the passed IBAN is valid, false otherwise
     */
    exports.isValid = function(iban){
        if (!isString(iban)){
            return false;
        }
        iban = this.electronicFormat(iban);
        var countryStructure = countries[iban.slice(0,2)];
        return !!countryStructure && countryStructure.isValid(iban);
    };

    /**
     * Convert an IBAN to a BBAN.
     *
     * @param iban
     * @param {String} [separator] the separator to use between the blocks of the BBAN, defaults to ' '
     * @returns {string|*}
     */
    exports.toBBAN = function(iban, separator){
        if (typeof separator == 'undefined'){
            separator = ' ';
        }
        iban = this.electronicFormat(iban);
        var countryStructure = countries[iban.slice(0,2)];
        if (!countryStructure) {
            throw new Error('No country with code ' + iban.slice(0,2));
        }
        return countryStructure.toBBAN(iban, separator);
    };

    /**
     * Convert the passed BBAN to an IBAN for this country specification.
     * Please note that <i>"generation of the IBAN shall be the exclusive responsibility of the bank/branch servicing the account"</i>.
     * This method implements the preferred algorithm described in http://en.wikipedia.org/wiki/International_Bank_Account_Number#Generating_IBAN_check_digits
     *
     * @param countryCode the country of the BBAN
     * @param bban the BBAN to convert to IBAN
     * @returns {string} the IBAN
     */
    exports.fromBBAN = function(countryCode, bban){
        var countryStructure = countries[countryCode];
        if (!countryStructure) {
            throw new Error('No country with code ' + countryCode);
        }
        return countryStructure.fromBBAN(this.electronicFormat(bban));
    };

    /**
     * Check the validity of the passed BBAN.
     *
     * @param countryCode the country of the BBAN
     * @param bban the BBAN to check the validity of
     */
    exports.isValidBBAN = function(countryCode, bban){
        if (!isString(bban)){
            return false;
        }
        var countryStructure = countries[countryCode];
        return countryStructure && countryStructure.isValidBBAN(this.electronicFormat(bban));
    };

    /**
     *
     * @param iban
     * @param separator
     * @returns {string}
     */
    exports.printFormat = function(iban, separator){
        if (typeof separator == 'undefined'){
            separator = ' ';
        }
        return this.electronicFormat(iban).replace(EVERY_FOUR_CHARS, "$1" + separator);
    };

    /**
     *
     * @param iban
     * @returns {string}
     */
    exports.electronicFormat = function(iban){
        return iban.replace(NON_ALPHANUM, '').toUpperCase();
    };

    /**
     * An object containing all the known IBAN specifications.
     */
    exports.countries = countries;

}));

},{}],2:[function(require,module,exports){
(function(window) {
    var re = {
        not_string: /[^s]/,
        number: /[diefg]/,
        json: /[j]/,
        not_json: /[^j]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-gijosuxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[\+\-]/
    }

    function sprintf() {
        var key = arguments[0], cache = sprintf.cache
        if (!(cache[key] && cache.hasOwnProperty(key))) {
            cache[key] = sprintf.parse(key)
        }
        return sprintf.format.call(null, cache[key], arguments)
    }

    sprintf.format = function(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, node_type = "", arg, output = [], i, k, match, pad, pad_character, pad_length, is_positive = true, sign = ""
        for (i = 0; i < tree_length; i++) {
            node_type = get_type(parse_tree[i])
            if (node_type === "string") {
                output[output.length] = parse_tree[i]
            }
            else if (node_type === "array") {
                match = parse_tree[i] // convenience purposes only
                if (match[2]) { // keyword argument
                    arg = argv[cursor]
                    for (k = 0; k < match[2].length; k++) {
                        if (!arg.hasOwnProperty(match[2][k])) {
                            throw new Error(sprintf("[sprintf] property '%s' does not exist", match[2][k]))
                        }
                        arg = arg[match[2][k]]
                    }
                }
                else if (match[1]) { // positional argument (explicit)
                    arg = argv[match[1]]
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++]
                }

                if (get_type(arg) == "function") {
                    arg = arg()
                }

                if (re.not_string.test(match[8]) && re.not_json.test(match[8]) && (get_type(arg) != "number" && isNaN(arg))) {
                    throw new TypeError(sprintf("[sprintf] expecting number but found %s", get_type(arg)))
                }

                if (re.number.test(match[8])) {
                    is_positive = arg >= 0
                }

                switch (match[8]) {
                    case "b":
                        arg = arg.toString(2)
                    break
                    case "c":
                        arg = String.fromCharCode(arg)
                    break
                    case "d":
                    case "i":
                        arg = parseInt(arg, 10)
                    break
                    case "j":
                        arg = JSON.stringify(arg, null, match[6] ? parseInt(match[6]) : 0)
                    break
                    case "e":
                        arg = match[7] ? arg.toExponential(match[7]) : arg.toExponential()
                    break
                    case "f":
                        arg = match[7] ? parseFloat(arg).toFixed(match[7]) : parseFloat(arg)
                    break
                    case "g":
                        arg = match[7] ? parseFloat(arg).toPrecision(match[7]) : parseFloat(arg)
                    break
                    case "o":
                        arg = arg.toString(8)
                    break
                    case "s":
                        arg = ((arg = String(arg)) && match[7] ? arg.substring(0, match[7]) : arg)
                    break
                    case "u":
                        arg = arg >>> 0
                    break
                    case "x":
                        arg = arg.toString(16)
                    break
                    case "X":
                        arg = arg.toString(16).toUpperCase()
                    break
                }
                if (re.json.test(match[8])) {
                    output[output.length] = arg
                }
                else {
                    if (re.number.test(match[8]) && (!is_positive || match[3])) {
                        sign = is_positive ? "+" : "-"
                        arg = arg.toString().replace(re.sign, "")
                    }
                    else {
                        sign = ""
                    }
                    pad_character = match[4] ? match[4] === "0" ? "0" : match[4].charAt(1) : " "
                    pad_length = match[6] - (sign + arg).length
                    pad = match[6] ? (pad_length > 0 ? str_repeat(pad_character, pad_length) : "") : ""
                    output[output.length] = match[5] ? sign + arg + pad : (pad_character === "0" ? sign + pad + arg : pad + sign + arg)
                }
            }
        }
        return output.join("")
    }

    sprintf.cache = {}

    sprintf.parse = function(fmt) {
        var _fmt = fmt, match = [], parse_tree = [], arg_names = 0
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree[parse_tree.length] = match[0]
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree[parse_tree.length] = "%"
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1
                    var field_list = [], replacement_field = match[2], field_match = []
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list[field_list.length] = field_match[1]
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== "") {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list[field_list.length] = field_match[1]
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list[field_list.length] = field_match[1]
                            }
                            else {
                                throw new SyntaxError("[sprintf] failed to parse named argument key")
                            }
                        }
                    }
                    else {
                        throw new SyntaxError("[sprintf] failed to parse named argument key")
                    }
                    match[2] = field_list
                }
                else {
                    arg_names |= 2
                }
                if (arg_names === 3) {
                    throw new Error("[sprintf] mixing positional and named placeholders is not (yet) supported")
                }
                parse_tree[parse_tree.length] = match
            }
            else {
                throw new SyntaxError("[sprintf] unexpected placeholder")
            }
            _fmt = _fmt.substring(match[0].length)
        }
        return parse_tree
    }

    var vsprintf = function(fmt, argv, _argv) {
        _argv = (argv || []).slice(0)
        _argv.splice(0, 0, fmt)
        return sprintf.apply(null, _argv)
    }

    /**
     * helpers
     */
    function get_type(variable) {
        return Object.prototype.toString.call(variable).slice(8, -1).toLowerCase()
    }

    function str_repeat(input, multiplier) {
        return Array(multiplier + 1).join(input)
    }

    /**
     * export to either browser or node.js
     */
    if (typeof exports !== "undefined") {
        exports.sprintf = sprintf
        exports.vsprintf = vsprintf
    }
    else {
        window.sprintf = sprintf
        window.vsprintf = vsprintf

        if (typeof define === "function" && define.amd) {
            define(function() {
                return {
                    sprintf: sprintf,
                    vsprintf: vsprintf
                }
            })
        }
    }
})(typeof window === "undefined" ? this : window);

},{}],3:[function(require,module,exports){
!function(root, factory) {
    "function" == typeof define && define.amd ? // AMD. Register as an anonymous module unless amdModuleId is set
    define([], function() {
        return root.svg4everybody = factory();
    }) : "object" == typeof exports ? module.exports = factory() : root.svg4everybody = factory();
}(this, function() {
    /*! svg4everybody v2.0.3 | github.com/jonathantneal/svg4everybody */
    function embed(svg, target) {
        // if the target exists
        if (target) {
            // create a document fragment to hold the contents of the target
            var fragment = document.createDocumentFragment(), viewBox = !svg.getAttribute("viewBox") && target.getAttribute("viewBox");
            // conditionally set the viewBox on the svg
            viewBox && svg.setAttribute("viewBox", viewBox);
            // copy the contents of the clone into the fragment
            for (// clone the target
            var clone = target.cloneNode(!0); clone.childNodes.length; ) {
                fragment.appendChild(clone.firstChild);
            }
            // append the fragment into the svg
            svg.appendChild(fragment);
        }
    }
    function loadreadystatechange(xhr) {
        // listen to changes in the request
        xhr.onreadystatechange = function() {
            // if the request is ready
            if (4 === xhr.readyState) {
                // get the cached html document
                var cachedDocument = xhr._cachedDocument;
                // ensure the cached html document based on the xhr response
                cachedDocument || (cachedDocument = xhr._cachedDocument = document.implementation.createHTMLDocument(""), 
                cachedDocument.body.innerHTML = xhr.responseText, xhr._cachedTarget = {}), // clear the xhr embeds list and embed each item
                xhr._embeds.splice(0).map(function(item) {
                    // get the cached target
                    var target = xhr._cachedTarget[item.id];
                    // ensure the cached target
                    target || (target = xhr._cachedTarget[item.id] = cachedDocument.getElementById(item.id)), 
                    // embed the target into the svg
                    embed(item.svg, target);
                });
            }
        }, // test the ready state change immediately
        xhr.onreadystatechange();
    }
    function svg4everybody(rawopts) {
        function oninterval() {
            // while the index exists in the live <use> collection
            for (// get the cached <use> index
            var index = 0; index < uses.length; ) {
                // get the current <use>
                var use = uses[index], svg = use.parentNode;
                if (svg && /svg/i.test(svg.nodeName)) {
                    var src = use.getAttribute("xlink:href");
                    if (polyfill && (!opts.validate || opts.validate(src, svg, use))) {
                        // remove the <use> element
                        svg.removeChild(use);
                        // parse the src and get the url and id
                        var srcSplit = src.split("#"), url = srcSplit.shift(), id = srcSplit.join("#");
                        // if the link is external
                        if (url.length) {
                            // get the cached xhr request
                            var xhr = requests[url];
                            // ensure the xhr request exists
                            xhr || (xhr = requests[url] = new XMLHttpRequest(), xhr.open("GET", url), xhr.send(), 
                            xhr._embeds = []), // add the svg and id as an item to the xhr embeds list
                            xhr._embeds.push({
                                svg: svg,
                                id: id
                            }), // prepare the xhr ready state change event
                            loadreadystatechange(xhr);
                        } else {
                            // embed the local id into the svg
                            embed(svg, document.getElementById(id));
                        }
                    }
                } else {
                    // increase the index when the previous value was not "valid"
                    ++index;
                }
            }
            // continue the interval
            requestAnimationFrame(oninterval, 67);
        }
        var polyfill, opts = Object(rawopts), newerIEUA = /\bTrident\/[567]\b|\bMSIE (?:9|10)\.0\b/, webkitUA = /\bAppleWebKit\/(\d+)\b/, olderEdgeUA = /\bEdge\/12\.(\d+)\b/;
        polyfill = "polyfill" in opts ? opts.polyfill : newerIEUA.test(navigator.userAgent) || (navigator.userAgent.match(olderEdgeUA) || [])[1] < 10547 || (navigator.userAgent.match(webkitUA) || [])[1] < 537;
        // create xhr requests object
        var requests = {}, requestAnimationFrame = window.requestAnimationFrame || setTimeout, uses = document.getElementsByTagName("use");
        // conditionally start the interval if the polyfill is active
        polyfill && oninterval();
    }
    return svg4everybody;
});
},{}],4:[function(require,module,exports){
(function (global){
/*
 * Unilend Autocomplete
 */

/*
@todo support AJAX results
@todo finesse keyboard up/down on results
*/

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)

// Case-insensitive selector `:Contains()`
jQuery.expr[':'].Contains = function(a, i, m) {
  return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
};

// AutoComplete Language
var Dictionary = require('Dictionary')
var AUTOCOMPLETE_LANG = require('../../../lang/AutoComplete.lang.json')
var __ = new Dictionary(AUTOCOMPLETE_LANG)

/*
 * AutoComplete
 * @class
 */
// var autoComplete = new AutoComplete( elemOrSelector, {..});
var AutoComplete = function ( elem, options ) {
  var self = this

  /*
   * Options
   */
  self.options = $.extend({
    input: elem, // The input element to take the text input
    target: false, // The target element to put the results
    ajaxUrl: false, // An ajax URL to send the term receive results from. If `false`, looks in target element for the text
    delay: 200, // A delay to wait before searching for the term
    minTermLength: 3, // The minimum character length of a term to find
    showEmpty: false, // Show autocomplete with messages if no results found
    showSingle: true // Show the autocomplete if only one result found
  }, options)

  // Properties
  // -- Use jQuery to select elem, distinguish between string, HTMLElement and jQuery Object
  self.$input = $(self.options.input)
  self.$target = $(self.options.target)

  // Needs an input element to be valid
  if ( self.$input.length === 0 ) return self.error('input element doesn\'t exist')

  // Create a new target element for the results
  if ( !self.options.target || self.$target.length === 0 ) {
    self.$target = $('<div class="autocomplete"><ul class="autocomplete-results"></ul></div>')
    self.$input.after(self.$target)
  }

  // Get base elem
  self.input = self.$input[0]
  self.target = self.$target[0]
  self.timer = undefined

  /*
   * Events
   */
  // Type into the input elem
  self.$input.on('keydown', function ( event ) {
    clearTimeout(self.timer)
    self.timer = setTimeout( self.findTerm, self.options.delay )

    // Escape key - hide
    if ( event.which === 27 ) {
      self.hide()
    }
  })

  // Hide autocomplete
  self.$input.on('autocomplete-hide', function ( event ) {
    // console.log('autocomplete-hide', self.input)
    self.hide()
  })

  // Click result to complete the input
  self.$target.on('click', '.autocomplete-results a', function ( event ) {
    event.preventDefault()
    self.$input.val($(this).text())
    self.hide()
  })

  // Keyboard operations on results
  self.$target.on('keydown', '.autocomplete-results a:focus', function ( event ) {
    // Move between results and input
    // @todo finesse keyboard up/down on results
    // -- Up key
    if ( event.which === 38 ) {
      // Focus on input
      if ( $(this).parents('li').is('.autocomplete-results li:eq(0)') ) {
        self.$input.focus()

      // Focus on previous result anchor
      } else {
        event.preventDefault()
        $(this).parents('li').prev('li').find('a').focus()
      }

    // -- Down key
    } else if ( event.which === 40 ) {
      event.preventDefault()
      $(this).parents('li').next('li').find('a').focus()

    // -- Press esc to clear the autocomplete and go back to the search
    } else if ( event.which === 27 ) {
      self.$input.focus()
      self.hide()

    // -- Press enter or right arrow on highlighted result to complete the input
    } else if ( event.which === 39 || event.which === 13 ) {
      self.$input.val($(this).text()).focus()
      self.hide()
    }
  })

  /*
   * Methods
   */
  // Find a term
  self.findTerm = function (term) {
    var results = []

    // No term given? Assume term is val() of elem
    if ( typeof term === 'undefined' || term === false ) term = self.$input.val()

    // Term length not long enough, abort
    if ( term.length < self.options.minTermLength ) return

    // Perform ajax search
    if ( self.options.ajaxUrl ) {
      self.findTermViaAjax(term)

    // Perform search within target for an element's whose children contain the text
    } else {
      results = self.$target.find('.autocomplete-results li:Contains(\''+term+'\')');
      self.showResults(term, results)
    }
  }

  // Find a term via AJAX
  self.findTermViaAjax = function (term) {
    $.ajax({
      url: self.options.ajaxUrl,
      data: {
        term: term
      },
      success: function (data, textStatus, xhr) {
        if ( textStatus === 'success' ) {
          // @todo support AJAX results
          // @note AJAX should return JSON object as array, e.g.:
          //       [ "Comment ça va ?", "Comment ?", "Want to leave a comment?" ]
          //       AutoComplete will automatically highlight the results text as necessary
          self.showResults(term, data)
        } else {
          self.warning('Ajax Error: '+textStatus, xhr)
        }
      },
      error: function (textStatus, xhr) {
        self.warning('Ajax Error: '+textStatus, xhr)
      }
    })
  }

  // Display the results
  self.showResults = function (term, results) {
    var reTerm = new RegExp('('+self.reEscape(term)+')', 'gi')

    // Remove any messages
    self.$target.find('li.autocomplete-message').remove()

    // Populate the target element results as HTML
    // -- If ajaxUrl is set, assume results will be array
    if ( self.options.ajaxUrl ) {
      var resultsHTML = '';
      $(results).each( function (i, item) {
        resultsHTML += '<li><a href="javascript:void(0)" tabindex="1">' + self.highlightTerm(term, item) + '</a></li>'
      })
      self.$target.find('.autocomplete-results').html(resultsHTML)

      // Select all results as jQuery collection for further operations
      results = self.$target.find('.autocomplete-results li')

    // -- If ajaxUrl is false, assume target already contains items, and that results
    //    is a jQuery collection of those result elements which match the found term
    } else {
      self.removeHighlights()
      self.$target.find('.autocomplete-results li').hide()
    }

    // No results
    if ( results.length === 0 ) {

      // Show no results message
      if ( self.options.showEmpty ) {
        if ( self.$target.find('.autocomplete-results li.empty').length === 0 ) {
          self.$target.find('.autocomplete-results').append('<li class="autocomplete-message no-results">'+__.__('No results found!', 'noResults')+'</li>')
        }
        self.$target.find('.autocomplete-results li.no-results').show()
      } else {
        self.hide()
        return
      }

    // Results!
    } else {
      // Hide if only 1 result available and options.showSingle is disabled
      if ( results.length === 1 && !self.options.showSingle ) {
        self.hide()
        return
      }

      // Show the results
      self.highlightResults(term, results)
      results.show()
    }

    self.show()
  }

  // Escape a string for regexp purposes
  // See: http://stackoverflow.com/a/6969486
  self.reEscape = function (str) {
    return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")
  }

  // Add highlights to the results
  self.highlightResults = function (term, results) {
    results.each( function (i, item) {
      var text = $(this).find('a').text()
      var newText = self.highlightTerm(term, text)
      $(this).find('a').html(newText)
    })
  }

  // Add highlight to string
  self.highlightTerm = function (term, str) {
    var reTerm = new RegExp( '('+self.reEscape(term)+')', 'gi')
    return str.replace(reTerm, '<span class="highlight">$1</span>')
  }

  // Remove highlights from the text
  self.removeHighlights = function () {
    self.$target.find('.highlight').contents().unwrap()
  }

  // Show the autocomplete
  self.show = function () {
    // @debug console.log( 'show AutoComplete')

    self.$target.show()

    // Accessibility
    self.$target.attr('aria-hidden', 'false').find('.autocomplete-results li a').attr('tabindex', 1)
  }

  // Hide the autocomplete
  self.hide = function () {
    // @debug console.log( 'hide AutoComplete')

    clearTimeout(self.timer)
    self.$target.hide()

    // Accesibility
    self.$target.attr('aria-hidden', 'true').find('.autocomplete-results li a').attr('tabindex', -1)
  }

  // Hard error
  self.error = function () {
    throw new Error.apply(self, arguments)
    return
  }

  // Soft error (console warning)
  self.warning = function () {
    // if ( window.console ) if ( console.log ) {
    //   console.log('[AutoComplete Error]')
    //   console.log.apply(self, arguments)
    // }
  }

  /*
   * Initialise
   */
  // Assign direct AutoComplete reference to the input and target elems
  self.input.AutoComplete = self
  self.target.AutoComplete = self

  // Return the AutoComplete object
  return self
}

// module.exports = AutoComplete
module.exports = AutoComplete

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"../../../lang/AutoComplete.lang.json":21,"Dictionary":12}],5:[function(require,module,exports){
(function (global){
/*
 * Dashboard Panel
 */

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')

var DashboardPanel = function (elem, options) {
  var self = this
  self.$elem = $(elem)
  if (self.$elem.length === 0) return

  // Needs an ID number
  self.id = self.$elem.attr('id') || randomString()
  if (!self.$elem.attr('id')) self.$elem.attr('id', self.id)
  self.title = self.$elem.find('.dashboard-panel-title').text()

  // Settings
  self.settings = $.extend({
    draggable: true
  }, ElementAttrsObject(elem, {
    draggable: 'data-draggable'
  }), options)

  // Assign the class to show the UI functionality has been applied
  self.$elem.addClass('ui-dashboard-panel')

  // Show the panel
  self.show = function () {
    var self = this
    self.$elem.removeClass('ui-dashboard-panel-hidden')
    self.getToggles().removeClass('ui-dashboard-panel-hidden')
    self.refreshLayout()
  }

  // Hide the panel
  self.hide = function () {
    var self = this
    self.$elem.addClass('ui-dashboard-panel-hidden')
    self.getToggles().addClass('ui-dashboard-panel-hidden')
    self.refreshLayout()
  }

  // Toggle the panel
  self.toggle = function () {
    var self = this
    if (self.$elem.is('.ui-dashboard-panel-hidden')) {
      self.show()
    } else {
      self.hide()
    }
  }

  // Refresh any layout modules
  self.refreshLayout = function () {
    var self = this

    // Refresh packery
    if (self.$elem.parents('[data-packery]').length > 0) {
      self.$elem.parents('[data-packery]').packery()
    }
  }

  // Get any item which toggles this dashboard panel
  self.getToggles = function () {
    var self = this
    return $('[href="#' + self.id + '"].dashboard-panel-toggle')
  }

  // Trigger hide
  if (self.$elem.is('.ui-dashboard-panel-hidden')) {
    self.hide()
  } else {
    self.show()
  }

  self.$elem[0].DashboardPanel = self
  return self
}

/*
 * jQuery Plugin
 */
$.fn.uiDashboardPanel = function (op) {
  return this.each(function (i, elem) {
    new DashboardPanel(elem, op)
  })
}

/*
 * jQuery Initialisation
 */
$(document)
  .on('ready', function () {
    $('.dashboard-panel, [data-dashboardpanel]').uiDashboardPanel()
  })

  // Toggle the panel via the toggle option
  .on(Utility.clickEvent, '.dashboard-panel-toggle', function (event) {
    event.preventDefault()
    var $panel = $($(this).attr('href'))
    if ($panel.length > 0) $panel[0].DashboardPanel.toggle()
  })

  // Hide the panel via the close button
  .on(Utility.clickEvent, '.dashboard-panel .btn-close', function (event) {
    event.preventDefault()
    var $panel = $(this).parents('.dashboard-panel')
    $panel[0].DashboardPanel.hide()
  })

module.exports = DashboardPanel

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"ElementAttrsObject":13,"Utility":17}],6:[function(require,module,exports){
(function (global){
/*
 * Unilend File Attach
 * Enables extended UI for attaching/removing files to a form
 */

// @TODO integrate Dictionary
// @TODO may need AJAX functionality

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')

function randomString (stringLength) {
  var output = ''
  var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
  stringLength = stringLength || 8
  for (var i = 0; i < stringLength; i++) {
    output += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return output
}

function getFileSizeUnits (fileSizeInBytes) {
  if (fileSizeInBytes < 1024) {
    return 'B'
  } else if (fileSizeInBytes >= 1024 && fileSizeInBytes < 1048576) {
    return 'KB'
  } else if (fileSizeInBytes >= 1048576 && fileSizeInBytes < 1073741824) {
    return 'MB'
  } else if (fileSizeInBytes >= 1073741824) {
    return 'GB'
  }
}

function getFileSizeWithUnits (fileSizeInBytes) {
  var units = getFileSizeUnits(fileSizeInBytes)
  var fileSize = fileSizeInBytes

  switch (units) {
    case 'B':
      return fileSize + ' ' + units

    case 'KB':
      fileSize = fileSize / 1024
      return Math.floor(fileSize) + ' ' + units

    case 'MB':
      fileSize = fileSize / 1048576
      return fileSize.toFixed(1) + ' ' + units

    case 'GB':
      fileSize = fileSize / 1073741824
      return fileSize.toFixed(2) + ' ' + units
  }
}

var FileAttach = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0) return

  // Settings
  // -- Defaults
  self.settings = $.extend({
    // Properties
    maxFiles: 0,
    maxSize: (1024 * 1024 * 8),
    fileTypes: 'pdf jpg jpeg png doc docx',
    inputName: 'fileattach',

    // Template
    templates: {
      fileItem: '<label class="ui-fileattach-item"><span class="label">{{ fileName }}</span><input type="file" name="{{ inputName }}[{{ fileId }}]" value=""/><a href="javascript:;" class="ui-fileattach-remove-btn"><span class="sr-only"> Remove</span></a></label>',
      errorMessage: '<div class="ui-fileattach-error"><span class="c-error">{{ errorMessage }}</span> {{ message }}</div> <span class="ui-fileattach-add-btn">Select file...</span>'
    },

    // Labels
    lang: {
      emptyFile: '<span class="ui-fileattach-add-btn">Select file...</span>',
      errors: {
        incorrectFileType: {
          errorMessage: 'File type <strong>{{ fileType }}</strong> not accepted',
          message: 'Accepting only: <strong>{{ acceptedFileTypes }}</strong>'
        },
        incorrectFileSize: {
          errorMessage: 'File size exceeds maximum <strong>{{ acceptedFileSize }}</strong>',
          message: ''
        }
      }
    },

    // Events
    onadd: function () {},
    onremove: function () {}
  },
  // -- Options (via element attributes)
  ElementAttrsObject(elem, {
    maxFiles: 'data-fileattach-maxfiles',
    maxSize: 'data-fileattach-maxsize',
    fileTypes: 'data-fileattach-filetypes',
    inputName: 'data-fileattach-inputname'
  }),
  // -- Options (via JS method call)
  options)

  // Elements
  self.$elem.addClass('ui-fileattach')

  // Add a file item to the list
  self.add = function (inhibitPrompt) {
    var self = this
    var $files = self.getFiles()
    var $emptyItems = self.$elem.find('.ui-fileattach-item').not('[data-fileattach-item-type]')

    // See if any are empty and select that instead
    if ($emptyItems.length > 0) {
      if (!inhibitPrompt || typeof inhibitPrompt === 'undefined') $emptyItems.first().click()
      return
    }

    // Prevent more files being added
    if (self.settings.maxFiles > 0 && $files.length >= self.settings.maxFiles) return

    // Append a new file input
    var $file = $(Templating.replace(self.settings.templates.fileItem, {
      fileName: self.settings.lang.emptyFile,
      inputName: self.settings.inputName,
      fileId: randomString()
    }))
    $file.appendTo(self.$elem)

    // @debug
    // console.log('add fn', inhibitPrompt, $file.find('[name]').first().attr('name'))

    if (!inhibitPrompt || typeof inhibitPrompt === 'undefined') $file.click()
  }

  // Attach a file
  self.attach = function (fileElem) {
    var self = this
    var $file = $(fileElem)

    // Get the file name
    var fileName = $file.val().split('\\').pop()
    var fileType = $file.val().split('.').pop().toLowerCase()
    var fileSize = 0

    // HTML5 Files support
    if (typeof $file[0].files !== 'undefined' && $file[0].files.length > 0) {
      if (typeof $file[0].files[0].type !== 'undefined') fileType = $file[0].files[0].type.split('/').pop()
      if (typeof $file[0].files[0].size !== 'undefined') fileSize = $file[0].files[0].size
    }

    // Reject file because of type
    if (self.settings.fileTypes !== '*' && !(new RegExp(' ' + fileType + ' ', 'i').test(' ' + self.settings.fileTypes + ' '))) {
      $file.val('')
        .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type')
        .find('.label').html(Templating.replace(self.settings.templates.errorMessage, [self.settings.lang.errors.incorrectFileType, {
          fileType: fileType.toUpperCase(),
          acceptedFileTypes: self.settings.fileTypes.split(/[, ]+/).join(', ').toUpperCase()
        }]))
      return
    }

    // Reject file because of size
    if (self.settings.maxSize && fileSize && fileSize > self.settings.maxSize) {
      $file.val('')
        .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type')
        .find('.label').html(Templating.replace(self.settings.templates.errorMessage, [self.settings.lang.errors.incorrectFileSize, {
          fileSize: getFileSizeWithUnits(fileSize),
          acceptedFileSize: getFileSizeWithUnits(self.settings.maxSize)
        }]))
      return
    }

    // Attach the file
    $file.parents('.ui-fileattach-item').attr({
      title: fileName,
      'data-fileattach-item-type': fileType
    }).find('.label').text(fileName)

    // @debug
    // console.log('attach fn')

    // If can add another...
    if (self.getFiles().length < self.settings.maxFiles) self.add(true)
  }

  // Remove a file
  self.remove = function (fileIndex) {
    var self = this
    if (typeof fileIndex === 'undefined') return

    // Get the file and label elements
    if (self.getFiles().length > 1) {
      self.getFile(fileIndex).parents('label').remove()
    } else {
      self.getFiles().val('')
        .parents('.ui-fileattach-item').removeAttr('data-fileattach-item-type')
        .find('.label').html(self.settings.lang.emptyFile)
    }

    // @debug
    // console.log('remove fn')

    // If can add another...
    if (self.getFiles().length < self.settings.maxFiles) self.add(true)

    self.$elem.trigger('FileAttach:removed', [self, fileIndex])
  }

  // Clear all files
  self.clear = function () {
    var self = this

    // Clear the list of files
    self.getFiles().each(function (i, file) {
      $(file).parents('.ui-fileattach-item').remove()
    })

    // Add new file
    self.add(true)

    self.$elem.trigger('FileAttached:cleared', [self])
  }

  // Get file elements
  self.getFiles = function () {
    var self = this
    return self.$elem.find('.ui-fileattach-item input[type="file"]')
  }

  // Get single file element
  self.getFile = function (fileIndex) {
    var self = this
    return self.getFiles().eq(fileIndex)
  }

  // Init
  if (self.getFiles().length === 0) {
    self.add(true)
  }
  self.$elem[0].FileAttach = self
}

// Events
$(document)
  // -- Click add button
  .on('click', '.ui-fileattach-add-btn', function (event) {
    event.preventDefault()
    $(this).parents('.ui-fileattach').uiFileAttach('add')
  })

  // -- Click remove button
  .on('click', '.ui-fileattach-remove-btn', function (event) {
    event.preventDefault()
    event.stopPropagation()

    // @debug
    // console.log('click remove btn')

    var $fa = $(this).parents('.ui-fileattach')
    var fa = $fa[0].FileAttach
    var $file = $(this).parents('.ui-fileattach-item').find('input[type="file"]')
    var fileIndex = fa.getFiles().index($file)
    $fa.uiFileAttach('remove', fileIndex)
  })

  // -- When the file input has changed
  .on('change', '.ui-fileattach input[type="file"]', function (event) {
    if ($(this).val()) $(this).parents('.ui-fileattach').uiFileAttach('attach', this)
  })

  // -- Document ready: auto-apply functionality to elements with [data-fileattach] attribute
  .on('ready', function () {
    $('[data-fileattach]').uiFileAttach()
  })

$.fn.uiFileAttach = function (op) {
  // Fire a command to the FileAttach object, e.g. $('[data-fileattach]').uiFileAttach('add', {..})
  if (typeof op === 'string' && /^add|attach|remove|clear$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.FileAttach && typeof elem.FileAttach[op] === 'function') {
        elem.FileAttach[op].apply(elem.FileAttach, args)
      }
    })

  // Set up a new FileAttach instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.FileAttach) {
        new FileAttach(elem, op)
      }
    })
  }
}

module.exports = FileAttach

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"ElementAttrsObject":13,"Templating":15}],7:[function(require,module,exports){
(function (global){
/*
 * Unilend Form Validation
 */

// @TODO Dictionary integration

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var sprintf = require('sprintf-js').sprintf
var Iban = require('iban')
var ElementAttrsObject = require('ElementAttrsObject')
var Templating = require('Templating')
var Dictionary = require('Dictionary')
var __ = new Dictionary({
  "en": {
    "errorFieldRequired": "Field cannot be empty",
    "errorFieldRequiredCheckbox": "Please check the box to continue",
    "errorFieldRequiredCheckboxes": "Please select an option to continue",
    "errorFieldRequiredRadio": "Please select an option to continue",
    "errorFieldRequiredSelect": "Please select an option to continue",
    "errorFieldMinLength": "Please ensure field is at least %d characters long",
    "errorFieldMaxLength": "Please ensure field does not exceed %d characters",
    "errorfieldInputTypeNumber": "Not a valid number",
    "errorfieldInputTypeEmail": "Not a valid email address",
    "errorfieldInputTypeTelephone": "Not a valid email telephone number"
  }
}, 'en')

function getLabelForElem (elem) {
  var $elem = $(elem)
  var label = ''
  var labelledBy = $elem.attr('aria-labelledby')
  var $label = $('label[for="' + $elem.attr('id') + '"]').first()

  // Labelled by other elements
  if (labelledBy) {
    label = []

    // Get elements that element has been labelled by
    if (/ /.test(labelledBy)) {
      labelledBy = labelledBy.split(' ')
    } else {
      labelledBy = [labelledBy]
    }

    $.each(labelledBy, function (i, label) {
      var $labelledBy = $('#' + label).first()
      if ($labelledBy.length > 0) {
        label.push($labelledBy.text())
      }
    })

    // Labels go in reverse order?
    label = label.reverse().join(' ')

  // Labelled by traditional method, e.g. label[for="id-of-element"]
  } else {
    if ($label.length > 0) {
      label = $label.text()

      // Label label
      if ($label.find('.label').length > 0) {
        label = $label.find('.label').text()
      }
    }
  }

  return label.replace(/\s+/g, ' ').trim()
}

// Get the field's value
function getFieldValue (elem) {
  var $elem = $(elem)
  var value = undefined

  // No elem
  if ($elem.length === 0) return value

  // Elem is a single input
  if ($elem.is('input, textarea, select')) {
    if ($elem.is('[type="radio"], [type="checkbox"]')) {
      if ($elem.is(':checked, :selected')) {
        value = $elem.val()
      }
    } else {
      value = $elem.val()
    }

  // Elem contains multiple inputs
  } else {
    // Search inside for inputs
    var $inputs = $elem.find('input, textarea, select')

    // No inputs
    if ($inputs.length === 0) return value

    // Get input values
    var inputNames = []
    var inputValues = {}
    $inputs.each(function (i, input) {
      var $input = $(input)
      var inputName = $input.attr('name')
      var inputValue = getFieldValue(input)

      if (typeof inputValue !== 'undefined') {
        if ($.inArray(inputName, inputNames) === -1) {
          inputNames.push(inputName)
        }
        inputValues[inputName] = $input.val()
      }
    })

    // @debug
    // console.log('getFieldValue:groupedinputs', {
    //   $inputs: $inputs,
    //   inputNames: inputNames,
    //   inputValues: inputValues
    // })

    // The return value
    if (inputNames.length === 1) {
      value = inputValues[inputNames[0]]
    } else if (inputNames.length > 1) {
      value = inputValues
    }
  }

  // @debug
  // console.log('getFieldValue', value)

  return value
}

// Get the field's input type
function getFieldType (elem) {
  var $elem = $(elem)
  var type = undefined

  // Error
  if ($elem.length === 0) return undefined

  // Single inputs
  if ($elem.is('input')) {
    type = $elem.attr('type')

  } else if ($elem.is('textarea')) {
    type = 'text'

  } else if ($elem.is('select')) {
    type = 'select'

  // Grouped inputs
  } else if (!$elem.is('input, select, textarea')) {
    // Get all the various input types within this element
    var $inputs = $elem.find('input, select, textarea')
    if ($inputs.length > 0) {
      var inputTypes = []
      $inputs.each(function (i, input) {
        var inputType = getFieldType(input)
        if (inputType && $.inArray(inputType, inputTypes) === -1) inputTypes.push(inputType)
      })

      // Put into string to return
      if ($inputs.length > 1) inputTypes.unshift('multi')
      if (inputTypes.length > 0) type = inputTypes.join(' ')

      // @debug
      // console.log('getFieldType:non_input', {
      //   inputTypes: inputTypes,
      //   $inputs: $inputs
      // })
    }
  }

  // @debug
  // console.log('getFieldType', {
  //   elem: elem,
  //   type: type
  // })

  return type
}

var FormValidation = function (elem, options) {
  var self = this
  self.$elem = $(elem)

  // Error
  if (self.$elem.length === 0) return

  // Settings
  self.settings = $.extend({
    // The form
    formElem: false,

    // An element that contains notifications (i.e. messages to send to the user)
    notificationsElem: false,

    // Whether to validate on the form or on the individual field event
    validateOnFormEvents: true,
    validateOnFieldEvents: true,

    // The specific events to watch to trigger the form/field validation
    watchFormEvents: 'submit',
    watchFieldEvents: 'keydown blur change',

    // Show successful/errored validation on field
    showSuccessOnField: true,
    showErrorOnField: true,
    showAllErrors: false,

    // Update the view (disable if you have your own rendering callbacks)
    render: true,

    // The callback to fire before validating a field
    onfieldbeforevalidate: function () {},

    // The callback to fire after validating a field
    onfieldaftervalidate: function () {},

    // The callback to fire when a field passed validation successfully
    onfieldsuccess: function () {},

    // The callback to fire when a field did not pass validation
    onfielderror: function () {},

    // The callback to fire before form/group validation
    onbeforevalidate: function () {},

    // The callback to fire after form/group validation
    onaftervalidate: function () {},

    // The callback to fire when the form/group passed validation successfully
    onsuccess: function () {},

    // The callback to fire when the form/group did not pass validation
    onerror: function () {},

    // The callback to fire when the form/group completed validation
    oncomplete: function () {}
  }, ElementAttrsObject(elem, {
    formElem: 'data-formvalidation-formelem',
    notificationsElem: 'data-formvalidation-notificationselem',
    render: 'data-formvalidation-render'
  }), options)

  // Properties
  // -- Get the target form that this validation component is referencing
  if (self.$elem.is('form')) self.$form = self.$elem
  if (self.settings.formElem) self.$form = $(self.settings.formElem)
  if (!self.$form || self.$form.length === 0) self.$form = self.$elem.parents('form').first()
  // -- Get the notifications element
  self.$notifications = $(self.settings.notificationsElem)
  if (self.$notifications.length > 0 && !$.contains(document, self.$notifications[0])) self.$form.prepend(self.$notifications)

  // @debug
  // console.log({
  //   $elem: self.$elem,
  //   $form: self.$form,
  //   settings: self.settings
  // })

  // Setup UI
  self.$elem.addClass('ui-formvalidation')

  /*
   * Methods
   */
  // Validate a single form field
  self.validateField = function (elem, options) {
    var self = this
    var $elem = $(elem).first()

    // Error
    if ($elem.length === 0) return false

    // Field is group of related inputs: checkbox, radio
    if (!$elem.is('input, textarea, select')) {
      // @debug
      // console.log('FormValidation.validateField Error: make sure the elem is—or contains—a input, textarea or select')
      if ($elem.find('input, textarea, select').length === 0) return false
    }

    // Ignore disabled fields
    if ($elem.is(':disabled')) return false

    // Field validation object
    var fieldValidation = {
      isValid: false,
      $elem: $elem,
      $formField: $elem.parents('.form-field').first(),
      value: getFieldValue($elem),
      type: 'auto', // Set to auto-detect type
      errors: [],
      messages: [],
      options: $.extend({
        // Rules to validate this field by
        rules: $.extend({}, self.rules.defaultRules,
        // Inherit attribute values
        ElementAttrsObject(elem, {
          required: 'data-formvalidation-required',
          minLength: 'data-formvalidation-minlength',
          maxLength: 'data-formvalidation-maxlength',
          setValues: 'data-formvalidation-setvalues',
          inputType: 'data-formvalidation-type',
          sameValueAs: 'data-formvalidation-samevalueas'
        })),

        // Options
        render: self.settings.render, // render the errors/messages to the UI
        showSuccess: self.settings.showSuccessOnField, // show that the input successfully validated
        showError: self.settings.showErrorOnField, // show that the input errored on validation
        showAllErrors: self.settings.showAllErrors, // show all the errors on the field, or show one by one

        // Events (in order of firing)
        onbeforevalidate: self.settings.onfieldbeforevalidate,
        onaftervalidate: self.settings.onfieldaftervalidate,
        onsuccess: self.settings.onfieldsuccess,
        onerror: self.settings.onfielderror,
        oncomplete: self.settings.onfieldcomplete
      }, ElementAttrsObject(elem, {
        rules: 'data-formvalidation-rules',
        showSuccess: 'data-formvalidation-showsuccess',
        showError: 'data-formvalidation-showerror',
        render: 'data-formvalidation-render'
      }), options)
    }

    // If rules was set (JSON), expand out into the options
    if (typeof fieldValidation.rules === 'string') {
      var checkRules = JSON.parse(fieldValidation.rules)
      if (typeof checkRules === 'object') {
        fieldValidation.options = $.extend(fieldValidation.options, checkRules)
      }
    }

    // Auto-select inputType validation
    if (fieldValidation.type === 'auto') {
      fieldValidation.type = getFieldType($elem)
    }

    // Make sure to always check non-text input types with the inputType rule
    if (fieldValidation.type !== 'text' && (typeof fieldValidation.options.rules.inputType === 'undefined' || fieldValidation.options.rules.inputType === false)) {
      fieldValidation.options.rules.inputType = fieldValidation.type
    }

    // @debug
    // console.log('fieldValidation', fieldValidation)

    // Before validation you can run a custom callback to process the field value
    if (typeof fieldValidation.options.onbeforevalidate === 'function') {
      fieldValidation.options.onbeforevalidate.apply($elem[0], [self, fieldValidation])
    }
    fieldValidation.$elem.trigger('FormValidation:validateField:beforevalidate', [self, fieldValidation])

    /*
     * Apply the validation rules
     */
    for (var i in fieldValidation.options.rules) {
      // @debug
      // console.log('fieldValidation rules: ' + i, fieldValidation.options.rules[i])
      if (fieldValidation.options.rules[i] && self.rules.hasOwnProperty(i) && typeof self.rules[i] === 'function') {
        // @debug
        // console.log('validating via rule: ' + i, 'condition: ', fieldValidation.options.rules[i])
        self.rules[i].apply(self, [fieldValidation, fieldValidation.options.rules[i]])
      }
    }

    // After validation you can run a custom callback on the results before shown in UI
    if (typeof fieldValidation.options.onaftervalidate === 'function') {
      fieldValidation.options.onaftervalidate.apply($elem[0], [self, fieldValidation])
    }
    fieldValidation.$elem.trigger('FormValidation:validateField:aftervalidate', [self, fieldValidation])

    // Field validation errors
    fieldValidation.$formField.removeClass('ui-formvalidation-error ui-formvalidation-success')
    if (fieldValidation.errors.length > 0) {
      fieldValidation.isValid = false

      // Trigger error
      if (typeof fieldValidation.options.onerror === 'function') {
        fieldValidation.options.onerror.apply(self, [self, fieldValidation])
      }
      fieldValidation.$elem.trigger('FormValidation:validateField:error', [self, fieldValidation])

      // Show error messages on the field
      if (fieldValidation.options.showError && fieldValidation.options.render) {
        // @debug
        // console.log('Validation error', fieldValidation.errors)
        fieldValidation.$formField.addClass('ui-formvalidation-error')
        fieldValidation.$formField.find('.ui-formvalidation-messages').html('')
        if (fieldValidation.errors.length > 0) {
          if (fieldValidation.$formField.find('.ui-formvalidation-messages').length === 0) {
            fieldValidation.$formField.append('<ul class="ui-formvalidation-messages"></ul>')
          }
          var formFieldErrorsHtml = ''
          $.each(fieldValidation.errors, function (i, item) {
            formFieldErrorsHtml += '<li>' + item.description + '</li>'
            if (!fieldValidation.options.showAllErrors) return false
          })
          fieldValidation.$formField.find('.ui-formvalidation-messages').html(formFieldErrorsHtml)
        }
      }

    // Field validation success
    } else {
      fieldValidation.isValid = true

      // Trigger error
      if (typeof fieldValidation.options.onsuccess === 'function') {
        fieldValidation.options.onsuccess.apply(self, [self, fieldValidation])
      }
      fieldValidation.$elem.trigger('FormValidation:validateField:success', [self, fieldValidation])

      // Show success messages on the field
      if (fieldValidation.options.showSuccess && fieldValidation.options.render) {
        // @debug
        // console.log('Validation success', fieldValidation.messages)
        fieldValidation.$formField.addClass('ui-formvalidation-success')
        fieldValidation.$formField.find('.ui-formvalidation-messages').html('')
        if (fieldValidation.messages.length > 0) {
          if (fieldValidation.$formField.find('.ui-formvalidation-messages').length === 0) {
            fieldValidation.$formField.append('<ul class="ui-formvalidation-messages"></ul>')
          }
          var formFieldMessagesHtml = ''
          $.each(fieldValidation.messages, function (i, item) {
            formFieldMessagesHtml += '<li>' + item.description + '</li>'
          })
          fieldValidation.$formField.find('.ui-formvalidation-messages').html(formFieldMessagesHtml)
        }
      }
    }

    // @debug
    // console.log('fieldValidation', fieldValidation)

    // Trigger complete
    if (typeof fieldValidation.options.oncomplete === 'function') {
      fieldValidation.options.oncomplete.apply(self, [self, fieldValidation])
    }
    fieldValidation.$elem.trigger('FormValidation:validateField:complete', [self, fieldValidation])

    return fieldValidation
  }

  // Validate the element's fields
  // @returns {Boolean}
  self.validate = function (options) {
    var self = this

    var groupValidation = $.extend({
      // Properties
      isValid: false,
      $elem: self.$elem,
      $notifications: self.$notifications,
      fields: [],
      validFields: [],
      erroredFields: [],

      // Options
      render: self.settings.render,

      // Events
      onbeforevalidate: self.settings.onbeforevalidate,
      onaftervalidate: self.settings.onaftervalidate,
      onerror: self.settings.onerror,
      onsuccess: self.settings.onsuccess,
      oncomplete: self.settings.oncomplete
    }, options)

    // Trigger before validate
    if (typeof groupValidation.onbeforevalidate === 'function') groupValidation.onbeforevalidate.apply(self, [self, groupValidation])
    groupValidation.$elem.trigger('FormValidation:validate:beforevalidate', [self, groupValidation])

    // Validate each field
    self.getFields().each(function (i, input) {
      var fieldValidation = self.validateField(input)
      groupValidation.fields.push(fieldValidation)

      // Filter collection via valid/errored
      if (fieldValidation.isValid) {
        groupValidation.validFields.push(fieldValidation)
      } else {
        groupValidation.erroredFields.push(fieldValidation)
      }
    })

    // Trigger after validate
    if (typeof groupValidation.onaftervalidate === 'function') groupValidation.onaftervalidate.apply(self, [self, groupValidation])
    groupValidation.$elem.trigger('FormValidation:validate:aftervalidate', [self, groupValidation])

    // Error
    groupValidation.$notifications.html('')
    if (groupValidation.erroredFields.length > 0) {
      groupValidation.isValid = false
      if (typeof groupValidation.onerror === 'function') groupValidation.onerror.apply(self, [self, groupValidation])
      groupValidation.$elem.trigger('FormValidation:validate:error', [self, groupValidation])

      // Render to view
      if (groupValidation.render) {
        groupValidation.$notifications.html('<div class="message-error"><p>There are errors with the form below. Please check ensure your information has been entered correctly before continuing.</p></div>')
      }

    // Success
    } else {
      groupValidation.isValid = true
      if (typeof groupValidation.onsuccess === 'function') groupValidation.onsuccess.apply(self, [self, groupValidation])
      groupValidation.$elem.trigger('FormValidation:validate:success', [self, groupValidation])
    }

    // Trigger complete
    if (typeof groupValidation.oncomplete === 'function') groupValidation.oncomplete.apply(self, [self, groupValidation])
    groupValidation.$elem.trigger('FormValidation:validate:complete', [self, groupValidation])

    return groupValidation
  }

  // Clears group's form fields of errors
  self.clear = function () {
    var self = this
    self.getFields().each(function (i, input) {
      $(elem)
        .parents('.form-field').removeClass('ui-formvalidation-error ui-formvalidation-success')
        .find('.ui-formvalidation-messages').html('')
    })
    self.$notifications.html('')
    self.$elem.trigger('FormValidation:clear', [self])
  }

  // Clears whole form (all groups)
  self.clearAll = function () {
    var self = this
    self.$form.uiFormValidation('clear')
      .find('[data-formvalidation]').uiFormValidation('clear')
  }

  // Get the collection of fields
  self.getFields = function () {
    var self = this
    return self.$elem.find('[data-formvalidation-field]')
  }

  // Events on the form
  self.$form.on(self.settings.watchFormEvents, function (event) {
    if (self.settings.validateOnFormEvents) {
      var formValidation = {
        isValid: false,
        groups: [],
        validGroups: [],
        erroredGroups: []
      }

      // Validate each group within the form
      $(this).find('[data-formvalidation]').each(function (i, elem) {
        var groupValidation = elem.FormValidation.validate()
        formValidation.groups.push(groupValidation)

        // Valid group
        if (groupValidation.isValid) {
          formValidation.validGroups.push(groupValidation)

        // Invalid group
        } else {
          formValidation.erroredGroups.push(groupValidation)
        }
      })

      // Error
      if (formValidation.erroredGroups.length > 0) {
        formValidation.isValid = false
        if (typeof self.settings.onerror === 'function') self.settings.onerror.apply(self, [self, formValidation])
        $(this).trigger('FormValidation:error', [self, formValidation])

      // Success
      } else {
        formValidation.isValid = true
        if (typeof self.settings.onsuccess === 'function') self.settings.onsuccess.apply(self, [self, formValidation])
        $(this).trigger('FormValidation:success', [self, formValidation])
      }

      // Stop any submitting happening
      if (!formValidation.isValid) return false
    }
  })

  // Events on the fields
  self.getFields().on(self.settings.watchFieldEvents, function (event) {
    if (self.settings.validateOnFieldEvents) {
      self.validateField(event.target)
    }
  })

  // console.log(self.getFields())

  // Attach FormValidation instance to element
  self.$elem[0].FormValidation = self
  return self
}

/*
 * Prototype functions and properties shared between all instances of FormValidation
 */
// The field validation rules to apply
// You can add custom new rules by adding to the prototype object
FormValidation.prototype.rules = {
  // Default rules per field validation
  defaultRules: {
    required: false,  // if the input is required
    minLength: false, // the minimum length of the input
    maxLength: false, // the maximum length of the input
    setValues: false, // list of possible set values to match to, e.g. ['on', 'off']
    inputType: false, // a keyword that matches the input to a an input type, e.g. 'text', 'number', 'email', 'date', 'url', etc.
    sameValueAs: false, // {String} selector, {HTMLElement}, {jQueryObject}
    custom: false // {Function} function (fieldValidation) { ..perform validation via fieldValidation object.. }
  },

  // Field must have value (i.e. not null or undefined)
  required: function (fieldValidation, isRequired) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof isRequired === 'undefined') isRequired = fieldValidation.options.rules.isRequired

    if (isRequired && (typeof fieldValidation.value === 'undefined' || typeof fieldValidation.value === 'null' || fieldValidation.value === '')) {

      // Checkbox is empty
      if (fieldValidation.type === 'checkbox') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please check the box to continue', 'errorFieldRequiredCheckbox')
        })

      // Multiple checkboxes are empty
      } else if (fieldValidation.type === 'multi checkbox') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'errorFieldRequiredCheckboxes')
        })

      // Radio is empty
      } else if (fieldValidation.type === 'radio' || fieldValidation.type === 'multi radio') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'errorFieldRequiredRadio')
        })

      } else if (fieldValidation.type === 'select') {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Please select an option to continue', 'errorFieldRequiredSelect')
        })

      // Other type of input is empty
      } else {
        fieldValidation.errors.push({
          type: 'required',
          description: __.__('Field value cannot be empty', 'errorFieldRequired')
        })
      }
    }

    // @debug
    // console.log('rules.required', fieldValidation)
  },

  // Minimum length
  minLength: function (fieldValidation, minLength) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof minLength === 'undefined') minLength = fieldValidation.options.rules.minLength

    if (minLength && fieldValidation.value.length < minLength) {
      fieldValidation.errors.push({
        type: 'minLength',
        description: sprintf(__.__('Please ensure field is at least %d characters long', 'errorFieldMinLength'), minLength)
      })
    }
  },

  // Maximum length
  maxLength: function (fieldValidation, maxLength) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof maxLength === 'undefined') maxLength = fieldValidation.options.rules.maxLength

    if (maxLength && fieldValidation.value.length > maxLength) {
      fieldValidation.errors.push({
        type: 'maxLength',
        description: sprintf(__.__('Please ensure field does not exceed %d characters', 'errorFieldMaxLength'), maxLength)
      })
    }
  },

  // Set Values
  setValues: function (fieldValidation, setValues) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof setValues === 'undefined') setValues = fieldValidation.options.rules.setValues

    if (setValues) {
      // Convert string to array
      if (typeof setValues === 'string') {
        if (/[\s,]+/.test(setValues)) {
          setValues = setValues.split(/[\s,]+/)
        } else {
          setValues = [setValues]
        }
      }

      // Check if value corresponds to one of the set values
      if (!$.inArray(fieldValidation.value, fieldValidation.options.setValues)) {
        fieldValidation.errors.push({
          type: 'setValues',
          description: __.__('Field value not accepted', 'errorFieldSetValues')
        })
      }
    }
  },

  // Input Type
  inputType: function (fieldValidation, inputType) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof inputType === 'undefined') inputType = fieldValidation.options.rules.inputType

    if (inputType) {
      switch (inputType.toLowerCase()) {
        case 'number':
          if (/[^\d-\.]+/.test(fieldValidation.value)) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Field accepts only numbers', 'errorFieldInputTypeNumber')
            })
          }
          break

        case 'phone':
        case 'telephone':
        case 'mobile':
          // Allowed: +33 644 911 250
          //          (0) 12.34.56.78.90
          //          856-6688
          if (!/^\+?[0-9\-\. ]{6,}$/.test(fieldValidation.value)) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid telephone number', 'errorFieldInputTypePhone')
            })
          }
          break

        case 'email':
          // Allowed: matt.scheurich@example.com
          //          mattscheurich@examp.le.com
          //          mattscheurich1983@example-email.co.nz
          //          matt_scheurich@example.email.address.net.nz
          if (!/^[a-z0-9\-_\.]+\@[a-z0-9\-\.]+\.[a-z0-9]{2,}(?:\.[a-z0-9]{2,})*$/i.test(fieldValidation.value)) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid email address', 'errorFieldInputTypeEmail')
            })
          }
          break

        case 'iban':
          // Uses npm library `iban` to validate
          if (!Iban.isValid(fieldValidation.value.replace(/\s+/g, ''))) {
            fieldValidation.errors.push({
              type: 'inputType',
              description: __.__('Not a valid IBAN number. Please ensure you have entered your number in correctly', 'errorFieldInputTypeIban')
            })
          }
          break
      }
    }
  },

  // Same Value as
  sameValueAs: function (fieldValidation, sameValueAs) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof sameValueAs === 'undefined') sameValueAs = fieldValidation.options.rules.sameValueAs

    if (sameValueAs) {
      var $compareElem = $(sameValueAs)
      if ($compareElem.length > 0) {
        if ($compareElem.val() != fieldValidation.value) {
          var compareElemLabel = getLabelForElem($compareElem).replace(/\*.*$/i, '')
          fieldValidation.errors.push({
            type: 'sameValueAs',
            description: sprintf(__.__('Field doesn\'t match %s', 'errorFieldSameValueAs'), '<label for="' + $compareElem.attr('id') + '"><strong>' + compareElemLabel + '</strong></label>')
          })
        }
      }
    }
  },

  // Custom
  custom: function (fieldValidation, custom) {
    // FormValidation
    var self = this

    // Default to fieldValidation option rule value
    if (typeof custom === 'undefined') custom = fieldValidation.options.rules.custom

    if (typeof custom === 'function') {
      // For custom validations, ensure you modify the fieldValidation object accordingly
      custom.apply(self, [self, fieldValidation])
    }
  }
}

/*
 * jQuery Plugin
 */
$.fn.uiFormValidation = function (op) {
  // Fire a command to the FormValidation object, e.g. $('[data-formvalidation]').uiFormValidation('validate', {..})
  if (typeof op === 'string' && /^validate|validateField|clear|clearAll$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.FormValidation && typeof elem.FormValidation[op] === 'function') {
        elem.FormValidation[op].apply(elem.FormValidation, args)
      }
    })

  // Set up a new FormValidation instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.FormValidation) {
        new FormValidation(elem, op)
      } else {
        $(elem).uiFormValidation('validate')
      }
    })
  }
}

/*
 * jQuery Events
 */
$(document)
  // -- Instatiate any element with [data-formvalidation] on ready
  .on('ready', function () {
    $('[data-formvalidation]').uiFormValidation()
  })

  // --

module.exports = FormValidation

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"Dictionary":12,"ElementAttrsObject":13,"Templating":15,"iban":1,"sprintf-js":2}],8:[function(require,module,exports){
(function (global){
/*
 * Unilend Password Check
 */

// @TODO integrate Dictionary

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)

// AutoComplete Language
var Dictionary = require('Dictionary')
var ElementAttrsObject = require('ElementAttrsObject')

function escapeQuotes (input) {
  return input.replace(/'/g, '&#39;').replace(/"/g, '&#34;')
}

var PasswordCheck = function (input, options) {
  var self = this
  self.$input = $(input)

  // Error: invalid element
  if (self.$input.length === 0 || !self.$input.is('input, textarea')) {
    console.log('PasswordCheck Error: given element is not an <input>', input)
    return
  }

  // Settings
  self.settings = $.extend(
  // Default settings
  {
    // Extra evaluation rules to check
    evaluationRules: [],

    // Max amount to score with all the rules
    maxScore: 15,

    // The minimum length of the password
    minLength: 8,

    // The UI elements to output messages or other feedback to (can be {String} selectors, {HTMLElement}s or {jQueryObject}s as well as {String} HTML code)
    levelElem: '<div class="ui-passwordcheck-level"><div class="ui-passwordcheck-level-bar"></div></div>',
    infoElem: '<div class="ui-passwordcheck-info"></div>',

    // The level of security the
    levels: [{
      name: 'weak-very',
      label: 'Very weak'
    },{
      name: 'weak',
      label: 'Weak'
    },{
      name: 'medium',
      label: 'Medium'
    },{
      name: 'strong',
      label: 'Strong'
    },{
      name: 'strong-very',
      label: 'Very strong'
    }],

    // Event to fire when an evaluation has completed
    // By default this updates the UI. If you are using custom UI elements to output to you may need to create your own version of this
    onevaluation: function (evaluation) {
      // Set level
      this.$level.find('.ui-passwordcheck-level-bar').css('width', evaluation.levelAmount * 100 + '%')
      this.$elem.addClass('ui-passwordcheck-checked ui-passwordcheck-level-' + evaluation.level.name)

      // Show description of evaluation
      var infoLabel = evaluation.level.label
      var infoMoreHtml = ''
      if (evaluation.info.length > 0) {
        infoLabel += ' <a href="javascript:;"><span class="icon fa-question-circle"></span></a>'
        infoMoreHtml += '<ul class="ui-passwordcheck-messages">'
        for (var i = 0; i < evaluation.info.length; i++) {
          var helpText = (typeof evaluation.info[i].help !== 'undefined' ? evaluation.info[i].help : '')
          var descriptionText = (typeof evaluation.info[i].description !== 'undefined' ? '<strong>' + evaluation.info[i].description + '</strong><br/>' : '')
          if (descriptionText || helpText) infoMoreHtml += '<li>' + descriptionText + helpText + '</li>'
        }
        infoMoreHtml += '</ul>'
      }
      this.$info.html('<div class="ui-passwordcheck-level-label">' + infoLabel + '</div>' + infoMoreHtml)
    }
  },
  // Get any settings/options from the element itself
  ElementAttrsObject(input, {
    minLength: 'data-passwordcheck-minlength',
    levelElem: 'data-passwordcheck-levelelem',
    infoElem: 'data-passwordcheck-infoelem'
  }),
  // Override with function call's options
  options)

  // UI elements
  self.$elem = self.$input.parents('.ui-passwordcheck')
  self.$level = $(self.settings.level)
  self.$info = $(self.settings.info)

  // Setup the UI
  // No main element? Wrap the input with a main element
  if (self.$elem.length === 0) {
    self.$input.wrap('<div class="ui-passwordcheck"></div>')
    self.$elem = self.$input.parents('.ui-passwordcheck')
  }
  // Ensure input has right classes and it's own wrap
  self.$input.addClass('ui-passwordcheck-input')
  if (self.$input.parents('.ui-passwordcheck-input-wrap').length === 0) {
    self.$input.wrap('<div class="ui-passwordcheck-input-wrap"></div>')
  }
  self.$level = $(self.settings.levelElem)
  self.$info = $(self.settings.infoElem)

  // If elements aren't already existing selectors, HTMLElements or objects, add them to the DOM in the correct places
  if (!$.contains(document, self.$level[0])) self.$input.after(self.$level)
  if (!$.contains(document, self.$info[0])) self.$elem.append(self.$info)

  // @debug
  // console.log({
  //   elem: self.$elem,
  //   input: self.$input,
  //   level: self.$level,
  //   info: self.$info
  // })

  // Reset the UI
  self.reset = function (soft) {
    var removeClasses = $.map(self.settings.levels, function (level) {
      return 'ui-passwordcheck-level-' + level.name
    }).join(' ')
    self.$elem.removeClass(removeClasses + ' ui-passwordcheck-checked')
    self.$level.find('.ui-passwordcheck-level-bar').css('width', 0)
    self.$info.html('')

    // Soft reset
    if (!soft) {
      self.$input.val('')
    }

    // Trigger element event in case anything else wants to hook
    self.$input.trigger('PasswordCheck:resetted', [self, soft])
  }

  // Evaluate an input value to see how secure it is
  self.evaluate = function (input) {
    if (typeof input === 'undefined') input = self.$input.val()
    if (typeof input === 'undefined') return false

    var complexity = 0
    var score = 0
    var level = ''
    var levelAmount = 0
    var info = []
    var evaluation = {}

    // The evaluation rules
    var evaluationRules = [{
      re: /[a-z]+/,
      amount: 1
    },{
      re: /[A-Z]+/,
      amount: 1
    },{
      re: /[0-9]+/,
      amount: 1
    },{
      re: /[\u2000-\u206F\u2E00-\u2E7F\\'!"#$%&()*+,\-.\/:;<=>?@\[\]^_`{|}~]+/,
      amount: 1
    },{
      re: /p[a4][s5]+(?:w[o0]+rd)?/i,
      amount: -1,
      description: 'Variations on the word "password"',
      help: 'Avoid using the word "password" or any other variation, e.g. "P455w0rD"'
    },{
      re: /asdf|qwer|zxcv|ghjk|tyiu|jkl;|nm,.|uiop/i,
      amount: -1,
      description: 'Combination matches common keyboard layouts',
      help: 'Avoid using common keyboard layout combinations'
    },{
      re: /([a-z0-9])\1{2,}/i,
      amount: -1,
      description: 'Repeated same character',
      help: 'Avoid repeating the same character. Add in more variation'
    },{
      re: /123(?:456789|45678|4567|456|45|4)?/,
      amount: -1,
      description: 'Incrementing number sequence',
      help: 'Avoid using incrementing numbers'
    },{
      re: /abc|xyz/i,
      amount: -1,
      description: 'Common alphabet sequences',
      help: 'Avoid using combinations like "abc" and "xyz"'
    }]
    if (self.settings.evaluationRules instanceof Array && self.settings.evaluationRules.length > 0) {
      evaluationRules += self.settings.evaluationRules
    }

    // Evaluate the string based on the minLength
    var inputLengthDiff = input.length - self.settings.minLength
    if (input.length < self.settings.minLength) {
      score -= 1
      info.push({
        description: 'Password is too short',
        help: 'Add extra words or characters to lengthen your password'
      })
    } else {
      score += 1
    }
    complexity += (inputLengthDiff > 0 ? Math.floor(inputLengthDiff / 8) : 0)

    // Evaluate the string based on the rules
    for (var i = 0; i < evaluationRules.length; i++) {
      var rule = evaluationRules[i]
      var ruleInfo = {}
      if (rule.hasOwnProperty('re') && rule.re instanceof RegExp) {
        // Positive match
        if (rule.re.test(input)) {
          score += rule.amount
          complexity += 1
          if (rule.hasOwnProperty('description')) ruleInfo.description = rule.description
          if (rule.hasOwnProperty('help')) ruleInfo.help = rule.help
          if (rule.hasOwnProperty('description') || rule.hasOwnProperty('help')) info.push(ruleInfo)
        }
      }
    }

    // Extra checks
    if (complexity < 3) {
      info.push({
        description: 'Password is potentially too simple',
        help: 'Use a combination of upper-case, lower-case, numbers and punctuation characters'
      })
    }

    // Turn score into a level
    levelAmount = (score * complexity) / self.settings.maxScore
    if (score < 0) levelAmount = 0 // Cap minimum
    if (levelAmount > 1) levelAmount = 1 // Cap maximum
    level = self.settings.levels[Math.floor(levelAmount * (self.settings.levels.length - 1))]

    evaluation = {
      score: score,
      complexity: complexity,
      levelAmount: levelAmount,
      level: level,
      info: info
    }

    // Fire the onevaluation event to update the UI
    self.reset(1)
    if (typeof self.settings.onevaluation === 'function') {
      self.settings.onevaluation.apply(self, [evaluation])
    }

    // Trigger element event in case anything else wants to hook
    self.$input.trigger('PasswordCheck:evaluation', [self, evaluation])

    // @debug
    // console.log('PasswordCheck.evaluate', evaluation)

    return evaluation
  }

  // Hook events to the element
  self.$input.on('keyup', function (event) {
    // Evaluate the element's input
    if ($(this).val().length > 0) {
      self.evaluate()

    // Reset the UI
    } else if ($(this).val().length === 0) {
      self.reset()
    }
  })

  // Show/hide the info
  self.$info.on('click', function (event) {
    event.preventDefault()
    self.$elem.toggleClass('ui-passwordcheck-info-open')
  })
}

/*
 * jQuery Plugin
 */
$.fn.uiPasswordCheck = function (options) {
  return this.each(function (i, elem) {
    new PasswordCheck(elem, options)
  })
}

/*
 * jQuery Events
 */
$(document)
  // Auto-assign functionality to elements with [data-passwordcheck] attribute
  .on('ready', function () {
    $('[data-passwordcheck]').uiPasswordCheck()
  })

module.exports = PasswordCheck

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"Dictionary":12,"ElementAttrsObject":13}],9:[function(require,module,exports){
(function (global){
/*
 * Unilend Sortable
 * Re-order elements within another element depending on a value
 */

// Dependencies
var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)

// Convert an input value (most likely a string) into a primitive, e.g. number, boolean, etc.
function convertToPrimitive (input) {
  // Non-string? Just return it straight away
  if (typeof input !== 'string') return input

  // Trim any whitespace
  input = (input + '').trim()

  // Number
  if (/^\-?(?:\d*[\.\,])*\d*(?:[eE](?:\-?\d+)?)?$/.test(input)) {
    return parseFloat(input)
  }

  // Boolean: true
  if (/^true|1$/.test(input)) {
    return true

  // NaN
  } else if (/^NaN$/.test(input)) {
    return NaN

  // undefined
  } else if (/^undefined$/.test(input)) {
    return undefined

  // null
  } else if (/^null$/.test(input)) {
    return null

  // Boolean: false
  } else if (/^false|0$/.test(input) || input === '') {
    return false
  }

  // Default to string
  return input
}

/*
 * Sortable class
 *
 * @class
 * @param {Mixed} elem Can be a {String} selector, {HTMLElement} or {jQueryElement}
 * @param {Object} options An object containing configurable settings for the sortable element
 * @returns {Sortable}
 */
var Sortable = function (elem, options) {
  var self = this

  // Invalid element
  if ($(elem).length === 0) return

  // Properties
  self.$elem = undefined
  self.$columns = undefined
  self.columnNames = []
  self.$content = undefined
  self.sortedColumn = false
  self.sortedDirection = false

  // Setup the Sortable
  return self.setup(elem, options)
}

/*
 * Get data attributes from an element (converts string values to JS primitives)
 *
 * @method attrsToObject
 * @param {Mixed} elem Can be a {String} selector, {HTMLElement} or {jQueryElement}
 * @param {Array} attrs An array of {Strings} which contain names of attributes to retrieve (these attributes will already be namespaced to fetch `data-sortable-{name}`)
 * @returns {Object}
 */
Sortable.prototype.attrsToObject = function (elem, attrs) {
  var $elem = $(elem).first()
  var self = this
  var output = {}

  if ($elem.length === 0 || !attrs) return output

  for (var i in attrs) {
    var attrValue = convertToPrimitive($elem.attr('data-sortable-' + attrs[i]))
    output[attrs[i]] = attrValue
  }

  return output
}

/*
 * Setup the element and properties
 *
 * @method setup
 * @param {Mixed} elem Can be a {String} selector, {HTMLElement} or {jQueryElement}
 * @param {Object} options An object containing configurable settings for the sortable element
 * @returns {Sortable}
 */
Sortable.prototype.setup = function (elem, options) {
  var self = this

  // Invalid element
  if ($(elem).length === 0) return

  // Unhook any previous elements
  if (self.$elem && self.$elem.length > 0) {
    self.$elem.removeClass('ui-sortable')
  }

  // Setup the properties
  self.$elem = $(elem).first()
  self.$columns = undefined
  self.columnNames = []
  self.$content = undefined
  self.sortedColumn = false
  self.sortedDirection = false

  // Get any settings applied to the element
  var elemSettings = Sortable.prototype.attrsToObject(elem, ['columns', 'content', 'saveoriginalorder'])

  // Settings with default values
  self.settings = $.extend({
    // The columns to sort by
    columns: '[data-sortable-by]',

    // The content to sort
    content: '[data-sortable-content]',

    // Sorting function
    onsortcompare: self.sortCompare,

    // Save the original order
    saveoriginalorder: true
  }, elemSettings, options)

  // Get/set the columns
  self.$columns = $(self.settings.columns)
  // -- Not found, look within element
  if (self.$columns.length === 0) {
    self.$columns = self.$elem.find(self.settings.columns)
    // -- Again, not found. Error!
    if (self.$columns.length === 0) {
      throw new Error('Sortable.setup error: no columns defined. Make sure you set each sortable column with the HTML attribute `data-sortable-by`')
    }
    // Set the column elements
    self.$columns = self.$columns

    // Get/set the column names
    self.$columns.each(function (i, elem) {
      var columnName = $(elem).attr('data-sortable-by')
      if (columnName) self.columnNames.push(columnName)
    })
  }

  // Get/set the content
  self.$content = $(self.settings.content)
  // -- Not found, look within element
  if (self.$content.length === 0) {
    self.$content = self.$elem.find(self.settings.content)
    // -- Again, not found. Error!
    if (self.$content.length === 0) {
      throw new Error('Sortable.setup error: no content defined. Make sure you set the sortable content with the HTML attribute `data-sortable-content`')
    }

    // Set the content element
    self.$content = self.$content.first()
  }

  // Save the original order
  if (self.settings.saveoriginalorder) {
    self.$content.children().each(function (i, item) {
      $(item).attr('data-sortable-original-order', i)
    })
  }

  // Trigger sortable element when ready
  self.$elem[0].Sortable = self
  self.$elem.trigger('sortable:ready')
  return self
}

/*
 * Sort the element's contents by a column name and in a particular direction
 * (if no direction given, it will toggle the direction)
 *
 * @method sort
 * @param {String} columnName The name of the column to sort by
 * @param {String} direction The direction to sort: `asc` | `desc`
 * @returns {Sortable}
 */
Sortable.prototype.sort = function (columnName, direction) {
  var self = this

  // Defaults
  columnName = columnName || 'original-order'
  direction = direction || 'asc'

  // Toggle sort direction
  if (self.sortedColumn === columnName || direction === 'toggle') {
    direction = (self.sortedDirection === 'asc' ? 'desc' : 'asc')
  }

  // Don't need to sort
  if (columnName === self.sortedColumn && direction === self.sortedDirection) return self

  // Get the new column to sort and compare
  var $sortColumn = self.$columns.filter('[data-sortable-by="' + columnName + '"]')

  // @debug console.log('Sortable.sort:', columnName, direction)

  // Trigger event before sort
  self.$elem.trigger('sortable:sort:before', [columnName, direction])

  // Do the sort in the UI
  self.$content.children().detach().sort(function (a, b) {
    return self.settings.onsortcompare.apply(self, [a, b, columnName, direction])
  }).appendTo(self.$content)

  // Update Sortable properties
  self.sortedColumn = columnName
  self.sortedDirection = direction

  // Update UI
  self.$columns.removeClass('ui-sortable-current ui-sortable-direction-asc ui-sortable-direction-desc')
  $sortColumn.addClass('ui-sortable-current ui-sortable-direction-' + direction)

  // Trigger event after the sort
  self.$elem.trigger('sortable:sort:after', [columnName, direction])

  return self
}

/*
 * Generic sorting comparison function (converts to floats and makes comparisons)
 * Uses the $.sort() method which compares 2 elements
 *
 * @method sortCompare
 * @param {Mixed} a The first item to compare
 * @param {Mixed} b The second item to compare
 * @param {String} columnName The name of the column to sort by
 * @param {String} direction The direction to sort (default is `asc`)
 * @returns {Number} Represents comparison: 0 | 1 | -1
 */
Sortable.prototype.sortCompare = function (a, b, columnName, direction) {
  if (!columnName) return 0

  // Get the values to compare based on the columnName
  a = convertToPrimitive($(a).attr('data-sortable-' + columnName))
  b = convertToPrimitive($(b).attr('data-sortable-' + columnName))
  output = 0

  // Get the direction to sort (default is `asc`)
  direction = direction || 'asc'
  switch (direction) {
    case 'asc':
    case 'ascending':
    case 1:
      if (a > b) {
        output = 1
      } else if (a < b) {
        output = -1
      }
      break

    case 'desc':
    case 'descending':
    case -1:
      if (a < b) {
        output = 1
      } else if (a > b) {
        output = -1
      }
      break
  }

  // @debug console.log('sortCompare', a, b, columnName, direction, output)
  return output
}

/*
 * Reset the contents' order back to the original
 *
 * @method reset
 * @returns {Sortable}
 */
Sortable.prototype.reset = function () {
  var self = this

  if (self.settings.saveoriginalorder) {
    self.$elem.trigger('sortable:reset')
    return self.sort('original-order', 'asc')
  }

  return self
}

/*
 * Destroy the Sortable instance
 *
 * @method destroy
 * @returns {Void}
 */
Sortable.prototype.destroy = function () {
  var self = this

  self.$elem[0].Sortable = false
  delete self
}

/*
 * jQuery API
 */
$.fn.uiSortable = function (op) {
  // Fire a command to the Sortable object, e.g. $('[data-sortable]').uiSortable('sort', 'id', 'asc')
  if (typeof op === 'string' && /^sort|reset$/.test(op)) {
    // Get further additional arguments to apply to the matched command method
    var args = Array.prototype.slice.call(arguments)
    args.shift()

    // Fire command on each returned elem instance
    return this.each(function (i, elem) {
      if (elem.Sortable && typeof elem.Sortable[op] === 'function') {
        elem.Sortable[op].apply(elem.Sortable, args)
      }
    })

  // Set up a new Sortable instance per elem (if one doesn't already exist)
  } else {
    return this.each(function (i, elem) {
      if (!elem.Sortable) {
        new Sortable(elem, op)
      }
    })
  }
}

// Auto-assign functionality to components with [data-sortable] attribute
$(document).on('ready', function () {
  $('[data-sortable]').uiSortable()
})

module.exports = Sortable

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{}],10:[function(require,module,exports){
(function (global){
/*
 * Unilend Text Counter
 */

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var ElementAttrsObject = require('ElementAttrsObject')
var Tween = require('Tween')
var __ = require('__')

var TextCount = function (elem, options) {
  var self = this

  /*
   * Properties
   */
  self.$elem = $(elem)
  self.elem = self.$elem[0]
  self.timer = false
  self.track = {}

  /*
   * Options
   */
  self.settings = $.extend({
    fps: 60,
    startCount: parseFloat(self.$elem.text()), // int/float
    endCount: 0, // int/float
    totalTime: 0, // in ms
    roundFloat: false, // how to round the float (and if)
    formatOutput: false
  }, ElementAttrsObject(elem, {
    fps: 'data-fps',
    startCount: 'data-start-count',
    endCount: 'data-end-count',
    totalTime: 'data-total-time',
    roundFloat: 'data-round-float'
  }), options)

  /*
   * UI
   */
  self.$elem.addClass('ui-text-count')

  /*
   * Initialising
   */
  // Ensure element has direct access to its TextCount
  self.elem.TextCount = self

  // Set the initial tracking values
  self.resetCount()

  // @debug console.log( self )

  return self
}

/*
 * Methods
 */
// Reset count
TextCount.prototype.resetCount = function () {
  var self = this
  self.stopCount()

  // Reset the tracking vars
  self.track = {
    fps:        parseInt(self.settings.fps, 10) || 60,      // int
    start:      parseFloat(String(self.settings.startCount).replace(/[^\d\-\.]+/g, '')) || 0,  // can be int/float
    current:    0,
    end:        parseFloat(self.settings.endCount) || 0,    // can be int/float
    total:      parseInt(self.settings.totalTime, 10) || 0, // int
    progress:   0 // float: from 0 to 1
  }

  self.track.timeIncrement = Math.ceil(self.track.total / self.track.fps) || 0
  self.track.increment = ((self.track.end - self.track.start) / self.track.timeIncrement) || 0
  self.track.current = self.track.start

  // Reset the count
  self.setText(self.track.current)
}

// Start counting
TextCount.prototype.startCount = function () {
  var self = this
  if ( self.countDirection() !== 0 && self.track.start != self.track.end ) {
    self.timer = setInterval( function () {
      self.incrementCount()
    }, self.track.timeIncrement )
  }
}

// Increment the count
TextCount.prototype.incrementCount = function () {
  var self = this
  // Increment the count
  var count = self.track.current = self.track.current + self.track.increment

  // Progress
  self.track.progress = (self.track.current / Math.max(self.track.start, self.track.end))

  // Round float
  if (self.settings.roundFloat) {
    switch (self.settings.roundFloat) {
      case 'round':
        count = Math.round(count)
        break

      case 'ceil':
        count = Math.ceil(count)
        break

      case 'floor':
        count = Math.floor(count)
        break
    }
  }

  // Set the count text
  self.setText(count)

  // End the count at end of progress
  if ( (self.countDirection() ===  1 && self.track.current < self.track.end) ||
       (self.countDirection() === -1 && self.track.current > self.track.end)    ) {
    self.endCount()
  }
}

// Set the text
TextCount.prototype.setText = function ( count ) {
  var self = this
  // Format the count
  if ( typeof self.settings.formatOutput === 'function' ) {
     count = self.settings.formatOutput.apply(self, [count])
  }

  // Set the element's text
  self.$elem.text(count)
}

// Stop counting
TextCount.prototype.stopCount = function () {
  var self = this
  clearTimeout(self.timer)
  self.timer = false
}

// Seek to end
TextCount.prototype.endCount = function () {
  var self = this
  self.stopCount()
  self.track.progress = 1
  self.track.current = self.track.end
  self.setText(self.track.end)
}

// Check if has started
TextCount.prototype.started = function () {
  var self = this
  return self.timer !== false
}

// Check if has stopped
TextCount.prototype.stopped = function () {
  var self = this
  return !self.timer
}

// Get direction of count
// 1: upward
// -1: downward
// 0: nowhere
TextCount.prototype.countDirection = function () {
  var self = this
  if ( self.track.start > self.track.end ) return  1
  if ( self.track.start < self.track.end ) return -1
  return 0
}

/*
 * jQuery Plugin
 */
$.fn.uiTextCount = function (op) {
  op = op || {}

  return this.each(function (i, elem) {
    // @debug
    // console.log('assign TextCount', elem)

    // Already assigned, ignore elem
    if (elem.hasOwnProperty('TextCount')) return

    var $elem = $(elem)
    var isPrice = /[\$\€\£]/.test($elem.text())
    var limitDecimal = $elem.attr('data-round-float') ? 0 : 2 // Set site-wide defaults here
    var tweenCount = $elem.attr('data-tween-count') || false // Set site-wide defaults here
    var debug = $elem.attr('data-debug') === 'true' // Output debug values for this item
    if (tweenCount && !Tween.hasOwnProperty(tweenCount)) tweenCount = false

    // Use separate functions here to reduce load within formatOutput callback
    if (tweenCount) {
      op.formatOutput = function (count) {
        // Tween the number
        var newCount = Tween[tweenCount].apply(this, [this.track.progress, this.track.start, Math.max(this.track.start, this.track.end) - Math.min(this.track.start, this.track.end), 1])

        // @debug if (debug) console.log(this.track.progress, count + ' => ' + newCount)

        // Format the output number
        return __.formatNumber(newCount, limitDecimal, isPrice)
      }
    } else {
      op.formatOutput = function (count) {
        // Format the output number
        return __.formatNumber(count, limitDecimal, isPrice)
      }
    }

    // Initialise the text count
    new TextCount(elem, op)

    // @debug
    // console.log('initialised TextCount', elem.TextCount)
  })
}

/*
 * jQuery Events
 */
$(document)
  // Initalise any element with the `.ui-text-count` class
  .on('ready', function () {
    // Applies to all generic .ui-text-count elements
    $('.ui-text-count').uiTextCount()
  })

module.exports = TextCount

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"ElementAttrsObject":13,"Tween":16,"__":19}],11:[function(require,module,exports){
(function (global){
/*
 * TimeCount
 */

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var Utility = require('Utility')
var ElementAttrsObject = require('ElementAttrsObject')
var __ = require('__')

// Overall timer for updating time counts (better in single timer than per element so all related time counters are updated at the same time)
var TimeCountTimer = setInterval(function () {
  var $timeCounters = $('.ui-time-counting')
  if ($timeCounters.length > 0) {
    $timeCounters.each(function (i, elem) {
      if (elem.hasOwnProperty('TimeCount')) {
        elem.TimeCount.update()
      }
    })
  }
}, 1000)

// TimeCount Class
var TimeCount = function (elem, options) {
  var self = this

  // The related element
  self.$elem = $(elem)
  if (self.$elem.length === 0) return

  // Settings
  self.settings = $.extend({
    startDate: false,
    endDate: false
  }, ElementAttrsObject(elem, {
    startDate: 'data-time-count-from',
    endDate: 'data-time-count-to'
  }), options)

  // Set up the dates
  if (self.settings.startDate && !(self.settings.startDate instanceof Date)) self.settings.startDate = new Date(self.settings.startDate)
  if (self.settings.endDate && !(self.settings.endDate instanceof Date)) self.settings.endDate = new Date(self.settings.endDate)

  // Track
  self.track = {
    direction: (self.settings.startDate > self.settings.endDate ? -1 : 1),
    timeRemaining: Utility.getTimeRemaining(self.settings.endDate, self.settings.startDate)
  }

  // UI
  self.$elem.addClass('ui-time-counting')

  // Attach reference to TimeCount to elem
  self.$elem[0].TimeCount = self

  // Trigger the starting event
  self.$elem.trigger('TimeCount:starting', [self, self.track.timeRemaining])

  // Update the time remaining
  self.update()

  return self
}

// Update the time count
TimeCount.prototype.update = function () {
  var self = this
  self.track.timeRemaining = Utility.getTimeRemaining(self.settings.endDate, self.settings.startDate)

  // Trigger the update event on the UI element
  self.$elem.trigger('TimeCount:update', [self, self.track.timeRemaining])

  // Count complete
  if ((self.track.direction > 0 && self.track.timeRemaining.total <= 0) ||
      (self.track.direction < 0 && self.track.timeRemaining.total >= 0)) {
    self.complete()
  }
}

// Complete the time count
TimeCount.prototype.complete = function () {
  var self = this

  // Trigger the completing event on the UI element
  self.$elem.trigger('TimeCount:completing', [self, self.track.timeRemaining])

  // Remove the .ui-time-counting class
  self.$elem.removeClass('.ui-time-counting')

  // Trigger the completed event on the UI element
  self.$elem.trigger('TimeCount:completed', [self, self.track.timeRemaining])
}

/*
 * jQuery plugin
 */
$.fn.uiTimeCount = function (op) {
  return this.each(function (i, elem) {
    if (!elem.hasOwnProperty('TimeCount')) {
      new TimeCount(elem, op)
    }
  })
}

/*
 * jQuery Initialisation
 */
$(document)
  .on('ready', function () {
    $('.ui-time-count').uiTimeCount()
  })

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"ElementAttrsObject":13,"Utility":17,"__":19}],12:[function(require,module,exports){
/*
 * Unilend Dictionary
 * Enables looking up text to change per user's lang
 * Works same as most i18n functions
 * This is also used by Twig to look up dictionary entries
 * See gulpfile.js to see how it is loaded into Twig
 */

var Dictionary = function (dictionary, lang) {
  var self = this

  if (!dictionary) return false

  self.defaultLang = lang || 'fr'
  self.dictionary = dictionary

  // Get a message within the dictionary
  self.__ = function (fallbackText, textKey, lang) {
    lang = lang || self.defaultLang

    // Ensure dictionary supports lang
    if (!self.supportsLang(lang)) {
      // See if general language exists
      if (lang.match('-')) lang = lang.split('-')[0]

      // Go to default
      if (!self.supportsLang(lang)) lang = self.defaultLang

      // Default not supported? Use the first lang entry in the dictionary
      if (!self.supportsLang(lang)) {
        for (x in self.dictionary) {
          lang = x
          break
        }
      }
    }

    // Ensure the textKey exists within the selected lang dictionary
    if (self.dictionary[lang].hasOwnProperty(textKey)) return dictionary[lang][textKey]

    // Fallback text
    return fallbackText

    // @debug console.log('Error: textKey not found => dictionary.' + lang + '.' + textKey)
    return '{# Error: textKey not found => dictionary.' + lang + '.' + textKey + ' #}'
  }

  // Set the default lang
  self.setDefaultLang = function (lang) {
    self.defaultLang = lang
  }

  // Check if the Dictionary supports a language
  self.supportsLang = function (lang) {
    return self.dictionary.hasOwnProperty(lang)
  }

  // Add seps to number (thousand and decimal)
  // Adapted from: http://www.mredkj.com/javascript/nfbasic.html
  self.addNumberSeps = function (number, milliSep, decimalSep, limitDecimal) {
    number += ''
    x = number.split('.')

    // Add the milliSep
    a = x[0]
    var rgx = /(\d+)(\d{3})/
    while (rgx.test(a)) {
      a = a.replace(rgx, '$1' + (milliSep || ',') + '$2')
    }

    // Limit the decimal
    if (limitDecimal > 0) {
      b = (x.length > 1 ? (decimalSep || '.') + x[1].substr(0, limitDecimal) : '')
    } else {
      b = ''
    }

    return a + b
  }

  // Format a number (adds punctuation, currency)
  self.formatNumber = function (input, limitDecimal, isPrice, lang) {
    var number = parseFloat(input + ''.replace(/[^\d\-\.]+/, ''))

    // Don't operate on non-numbers
    if (input === Infinity || isNaN(number)) return input

    // Language options
    var numberDecimal = self.__('.', 'numberDecimal', lang)
    var numberMilli = self.__(',', 'numberMilli', lang)
    var numberCurrency = self.__('$', 'numberCurrency', lang)

    // Is price
    // -- If not set, detect if has currency symbol in input
    var currency = numberCurrency
    if (typeof isPrice === 'undefined') {
      isPrice = /^[\$\€\£]/.test(input)
      if (isPrice) {
        currency = input.replace(/(^[\$\€\£])/g, '$1')
      }
    }

    // Default output
    var output = input

    // Limit the decimals shown
    if (typeof limitDecimal === 'undefined') {
      limitDecimal = isPrice ? 2 : 0
    }

    // Output the formatted number
    output = (isPrice ? currency : '') + self.addNumberSeps(number, numberMilli, numberDecimal, limitDecimal)

    // @debug
    // console.log({
    //   input: input,
    //   number: number,
    //   limitDecimal: limitDecimal,
    //   isPrice: isPrice,
    //   lang: lang,
    //   numberDecimal: numberDecimal,
    //   numberMilli: numberMilli,
    //   numberCurrency: numberCurrency,
    //   currency: currency,
    //   output: output
    // })

    return output
  }

  // Localize a number
  self.localizedNumber = function (input, limitDecimal, lang) {
    return self.formatNumber(input, limitDecimal || 0, false, lang)
  }

  // Localize a price
  self.localizedPrice = function (input, limitDecimal, lang) {
    return self.formatNumber(input, limitDecimal || 2, true, lang)
  }
}

module.exports = Dictionary

},{}],13:[function(require,module,exports){
(function (global){
/*
 * Element Attributes as Object
 *
 * Get a range of element attributes as an object
 */

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var Utility = require('Utility')

/*
 * @method ElementAttrsObject
 * @param {Mixed} elem Can be {String} selector, {HTMLElement} or {jQueryObject}
 * @param {Array} attrs An array of the possible attributes to retrieve from the element
 * @returns {Object}
 */
var ElementAttrsObject = function (elem, attrs) {
  var $elem = $(elem)
  var output = {}
  var attrValue
  var i

  // No element/attributes
  if ($elem.length === 0 || (typeof attrs !== 'object' && !(attrs instanceof Array))) return {}

  // Process attributes via array
  if (attrs instanceof Array) {
    for (i = 0; i < attrs.length; i++) {
      attrValue = Utility.checkElemAttrForValue(elem, attrs[i])
      if (typeof attrValue !== 'undefined') {
        output[attrs[i]] = Utility.convertToPrimitive(attrValue)
      }
    }

  // Process attributes via object key-value
  } else if (typeof attrs === 'object') {
    for (i in attrs) {
      attrValue = Utility.checkElemAttrForValue(elem, attrs[i])
      if (typeof attrValue !== 'undefined') {
        output[i] = Utility.convertToPrimitive(attrValue)
      }
    }
  }

  // @debug
  // console.log('ElementAttrsObject', elem, attrs, output)

  return output
}

module.exports = ElementAttrsObject

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"Utility":17}],14:[function(require,module,exports){
(function (global){
/*
 * Bounds
 * Get the bounds of an element
 * You can also perform other operations on the bounds (combine, scale, etc.)
 * This is used primarily by WatchScroll to detect if an element
 * is within the visible area of another element
 */

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var Utility = require('Utility')

function isElement(o){
  // Regular element check
  return (
    typeof HTMLElement === "object" ? o instanceof HTMLElement : //DOM2
    o && typeof o === "object" && o !== null && o.nodeType === 1 && typeof o.nodeName==="string"
  )
}

/*
 * Bounds object class
 */
var Bounds = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)

  // Properties
  self.id = Utility.randomString()
  self.coords = [0, 0, 0, 0]
  self.width = 0
  self.height = 0
  self.elem = undefined
  self.$viz = undefined

  // Initialise with any arguments, e.g. new Bounds(0, 0, 100, 100)
  if (args.length > 0) self.setBounds.apply(self, args)

  return self
}

// Set the bounds (and update width and height properties too)
// @returns {Bounds}
Bounds.prototype.setBounds = function () {
  var self = this

  // Check if 4 arguments given: x1, y1, x2, y2
  var args = Array.prototype.slice.call(arguments)

  // Single argument given
  if (args.length === 1) {
    // Bounds object given
    if (args[0] instanceof Bounds) {
      return args[0].clone()

    // 4 points given: x1, y1, x2, y2
    } else if (args[0] instanceof Array && args[0].length === 4) {
      args = args[0]

    // String or HTML element given
    } else if (typeof args[0] === 'string' || isElement(args[0]) || args[0] === window) {
      // @debug console.log('setBoundsFromElem', args[0])
      return self.setBoundsFromElem(args[0])
    }
  }

  // 4 coords given
  if (args.length === 4) {
    for (var i = 0; i < args.length; i++) {
      self.coords[i] = args[i]
    }
  }

  // Recalculate width and height
  self.width = self.getWidth()
  self.height = self.getHeight()

  return self
}

// Update the bounds (only if element is attached)
// @returns {Void}
Bounds.prototype.update = function () {
  var self = this

  // Only if related to an element
  if (self.elem) {
    self.setBoundsFromElem(self.elem)
  }

  self.width = self.getWidth()
  self.height = self.getHeight()

  // Update the viz
  if (self.getViz().length > 0) self.showViz()

  return self
}

// Calculate the width of the bounds
// @returns {Number}
Bounds.prototype.getWidth = function () {
  var self = this
  return self.coords[2] - self.coords[0]
}

// Calculate the height of the bounds
// @returns {Number}
Bounds.prototype.getHeight = function () {
  var self = this
  return self.coords[3] - self.coords[1]
}

// Scale the bounds: e.g. scale(2) => double, scale(1, 2) => doubles only height
// @returns {Bounds}
Bounds.prototype.scale = function () {
  var self = this
  var args = Array.prototype.slice.apply(arguments)
  var width
  var height
  var xScale = 1
  var yScale = 1

  // Depending on the number of arguments, scale the bounds
  switch (args.length) {
    case 0:
      return

    case 1:
      if (typeof args[0] === 'number') {
        xScale = args[0]
        yScale = args[0]
      }
      break

    case 2:
      if (typeof args[0] === 'number') xScale = args[0]
      if (typeof args[1] === 'number') yScale = args[1]
      break
  }

  // @debug console.log('Bounds.scale', xScale, yScale)

  // Scale
  if (xScale !== 1 || yScale !== 1) {
    width = self.getWidth()
    height = self.getHeight()
    self.setBounds(
      self.coords[0],
      self.coords[1],
      self.coords[0] + (width * xScale),
      self.coords[1] + (height * yScale)
    )
  }

  return self
}

// Combine with another bounds
// @returns {Bounds}
Bounds.prototype.combine = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)
  var totalBounds = self.clone().coords
  var newBounds

  if (args.length > 0) {
    // Process each item in the array
    for (var i = 0; i < args.length; i++) {
      // Bounds object given
      if (args[i] instanceof Bounds) {
        newBounds = args[i]

      // HTMLElement, String or Array given (x1, y1, x2, y2)
      } else if (typeof args[i] === 'string' || args[i] instanceof Array || isElement(args[i]) || args[0] === window) {
        newBounds = new Bounds(args[i])
      }

      // Combine
      if (newBounds) {
        for (var j = 0; j < newBounds.coords.length; j++) {
          // Set lowest/highest values of bounds
          if ((j < 2 && newBounds.coords[j] < totalBounds[j]) ||
              (j > 1 && newBounds.coords[j] > totalBounds[j])) {
            totalBounds[j] = newBounds.coords[j]
          }
        }
      }
    }

    // Set new combined bounds
    return self.setBounds(totalBounds)
  }
}

// Set bounds based on a DOM element
// @returns {Bounds}
Bounds.prototype.setBoundsFromElem = function (elem) {
  var self = this
  var $elem
  var elemWidth = 0
  var elemHeight = 0
  var elemOffset = {
    left: 0,
    top: 0
  }
  var elemBounds = [0, 0, 0, 0]
  var windowOffset = {
    left: $(window).scrollLeft(),
    top: $(window).scrollTop()
  }

  // Clarify elem objects
  if (!elem) elem = self.elem
  if (typeof elem === 'undefined') return self
  $elem = $(elem)
  if ($elem.length === 0) return self
  self.elem = elem = $elem[0]

  // Treat window object differently
  if (elem === window) {
    elemWidth = $(window).innerWidth()
    elemHeight = $(window).innerHeight()
    windowOffset.left = 0
    windowOffset.top = 0

  // Any other element
  } else {
    elemWidth = $elem.outerWidth()
    elemHeight = $elem.outerHeight()
    elemOffset = $elem.offset()
  }

  // Calculate the bounds
  elemBounds = [
    (elemOffset.left - windowOffset.left),
    (elemOffset.top - windowOffset.top),
    (elemOffset.left + elemWidth - windowOffset.left),
    (elemOffset.top + elemHeight - windowOffset.top)
  ]

  // @debug
  // self.showViz()
  // console.log('Bounds.setBoundsFromElem', {
  //   elem: elem,
  //   elemBounds: elemBounds,
  //   elemWidth: elemWidth,
  //   elemHeight: elemHeight,
  //   windowOffset: windowOffset
  // })

  // Instead of creating a new bounds object, just update these values
  self.coords = elemBounds
  self.width = self.getWidth()
  self.height = self.getHeight()

  // Set the bounds
  //return self.setBounds(elemBounds)
  return self
}

// Check if coords or bounds within another Bounds object
// @returns {Boolean}
Bounds.prototype.withinBounds = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)
  var totalBounds
  var visible = false

  // Calculate the total bounds
  for (var i = 0; i < args.length; i++) {
    var addBounds
    // Bounds object
    if (args[i] instanceof Bounds) {
      addBounds = args[i]

    // Array object
    } else if (args[i] instanceof Array) {
      // Single co-ord given (x, y)
      if (args[i].length === 2) {
        addBounds = new Bounds(args[i][0], args[i][1], args[i][0], args[i][1])

      // Pair of co-ords given (x1, y1, x2, y2)
      } else if (args[i].length === 4) {
        addBounds = new Bounds(args[i])
      }

    // Selector
    } else if (typeof args[i] === 'string') {
      addBounds = new Bounds().getBoundsFromElem(args[i])
    }

    // Add to total
    if (totalBounds) {
      totalBounds.combine(addBounds)

    // Create new total
    } else {
      totalBounds = addBounds
    }
  }

  // @debug
  // totalBounds.showViz()

  // See if this Bounds is within the totalBounds
  visible = self.coords[0] < totalBounds.coords[2] && self.coords[2] > totalBounds.coords[0] &&
            self.coords[1] < totalBounds.coords[3] && self.coords[3] > totalBounds.coords[1]

  return visible
}

Bounds.prototype.getCoordsVisibleWithinBounds = function () {
  var self = this
  var args = Array.prototype.slice.call(arguments)
  var totalBounds
  var coords = [false, false, false, false]

  // Calculate the total bounds
  for (var i = 0; i < args.length; i++) {
    var addBounds
    // Bounds object
    if (args[i] instanceof Bounds) {
      addBounds = args[i]

    // Array object
    } else if (args[i] instanceof Array) {
      // Single co-ord given (x, y)
      if (args[i].length === 2) {
        addBounds = new Bounds(args[i][0], args[i][1], args[i][0], args[i][1])

      // Pair of co-ords given (x1, y1, x2, y2)
      } else if (args[i].length === 4) {
        addBounds = new Bounds(args[i])
      }

    // Selector
    } else if (typeof args[i] === 'string') {
      addBounds = new Bounds().getBoundsFromElem(args[i])
    }

    // Add to total
    if (totalBounds) {
      totalBounds.combine(addBounds)

    // Create new total
    } else {
      totalBounds = addBounds
    }
  }

  // Check each coord
  if (self.coords[0] >= totalBounds.coords[0] && self.coords[0] <= totalBounds.coords[2]) coords[0] = self.coords[0]
  if (self.coords[1] >= totalBounds.coords[1] && self.coords[1] <= totalBounds.coords[3]) coords[1] = self.coords[1]
  if (self.coords[2] >= totalBounds.coords[0] && self.coords[2] <= totalBounds.coords[2]) coords[2] = self.coords[2]
  if (self.coords[3] >= totalBounds.coords[1] && self.coords[3] <= totalBounds.coords[3]) coords[3] = self.coords[3]

  return coords
}

// Get the offset between two bounds
// @returns {Bounds}
Bounds.prototype.getOffsetBetweenBounds = function (bounds) {
  var self = this

  var offsetCoords = [
    self.coords[0] - bounds.coords[0],
    self.coords[1] - bounds.coords[1],
    self.coords[2] - bounds.coords[2],
    self.coords[3] - bounds.coords[3]
  ]

  return new Bounds(offsetCoords)
}

// Creates a copy of the bounds
// @returns {Bounds}
Bounds.prototype.clone = function () {
  var self = this
  return new Bounds(self.coords)
}

// To string
// @returns {String}
Bounds.prototype.toString = function () {
  var self = this
  return self.coords.join(',')
}

// Bounds Visualiser
Bounds.prototype.getVizId = function () {
  var self = this
  var id = self.id
  if (self.elem) {
    id = $(self.elem).attr('id') || ($.isWindow(self.elem) ? 'window' : self.elem.name) || self.id
  }
  return 'bounds-viz-' + id
}

Bounds.prototype.getViz = function () {
  var self = this
  var $boundsViz = $('#' + self.getVizId())
  return $boundsViz
}

Bounds.prototype.showViz = function () {
  var self = this

  // @debug
  var $boundsViz = self.getViz()
  if ($boundsViz.length === 0) {
    $boundsViz = $('<div id="'+self.getVizId()+'" class="bounds-viz"></div>').css({
      position: 'fixed',
      backgroundColor: ['red','green','blue','yellow','orange'][Math.floor(Math.random() * 5)],
      opacity: .2,
      zIndex: 9999999
    }).appendTo('body')
  }

  // Set $viz element
  self.$viz = $boundsViz

  // Update viz properties
  $boundsViz.css({
    left: self.coords[0] + 'px',
    top: self.coords[1] + 'px',
    width: self.getWidth() + 'px',
    height: self.getHeight() + 'px'
  })

  return $boundsViz
}

Bounds.prototype.removeViz = function () {
  var self = this
  var $boundsViz = self.getViz()
  self.$viz = undefined
  $boundsViz.remove()
  return self
}

module.exports = Bounds

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"Utility":17}],15:[function(require,module,exports){
//
// Unilend JS Templating
// Very basic string replacement to allow templating
//

function replaceKeywordsWithValues (input, props) {
  output = input
  if (typeof output === 'undefined') return ''

  // Search for keywords
  var matches = output.match(/\{\{\s*[a-z0-9_\-\|]+\s*\}\}/gi)
  if (matches.length > 0) {
    for (var i = 0; i < matches.length; i++) {
      var propName = matches[i].replace(/^\{\{\s*|\s*\}\}$/g, '')
      var propValue = (props.hasOwnProperty(propName) ? props[propName] : '')

      // @debug
      // console.log('Templating', matches[i], propName, propValue)

      // Prop is functions
      // @note make sure custom functions return their final value as a string (or something human-readable)
      if (typeof propValue === 'function') propValue = propValue.apply(props)

      output = output.replace(new RegExp(matches[i], 'g'), propValue)
    }
  }

  return output
}

var Templating = {
  // Replaces instances of {{ propName }} in the template string with the corresponding value in the props object
  replace: function (input, props) {
    var output = input

    // Support processing props in sequential order with multiple objects
    if (!(props instanceof Array)) props = [props]
    for (var i = 0; i < props.length; i++) {
      output = replaceKeywordsWithValues(output, props[i])
    }

    return output
  }
}

module.exports = Templating

},{}],16:[function(require,module,exports){
//
// Tweening Functions
// @description adapted from http://gizma.com/easing/
//
// @param t current time
// @param b start value
// @param c change in value
// @param d duration

module.exports = {
  linearTween: function (t, b, c, d) {
    return c*t/d + b
  },

  easeInQuad: function (t, b, c, d) {
    t /= d
    return c*t*t + b
  },

  easeOutQuad: function (t, b, c, d) {
    t /= d
    return -c * t*(t-2) + b
  },

  easeInOutQuad: function (t, b, c, d) {
    t /= d/2
    if (t < 1) return c/2*t*t + b
    t--
    return -c/2 * (t*(t-2) - 1) + b
  },

  easeInCubic: function (t, b, c, d) {
    t /= d
    return c*t*t*t + b
  },

  easeOutCubic: function (t, b, c, d) {
    t /= d
    t--
    return c*(t*t*t + 1) + b
  },

  easeInOutCubic: function (t, b, c, d) {
    t /= d/2
    if (t < 1) return c/2*t*t*t + b
    t -= 2
    return c/2*(t*t*t + 2) + b
  },

  easeInQuart: function (t, b, c, d) {
    t /= d
    return c*t*t*t*t + b
  },

  easeOutQuart: function (t, b, c, d) {
    t /= d
    t--
    return -c * (t*t*t*t - 1) + b
  },

  easeInOutQuart: function (t, b, c, d) {
    t /= d/2
    if (t < 1) return c/2*t*t*t*t + b
    t -= 2
    return -c/2 * (t*t*t*t - 2) + b
  },

  easeInQuint: function (t, b, c, d) {
    t /= d
    return c*t*t*t*t*t + b
  },

  easeOutQuint: function (t, b, c, d) {
    t /= d
    t--
    return c*(t*t*t*t*t + 1) + b
  },

  easeInOutQuint: function (t, b, c, d) {
    t /= d/2
    if (t < 1) return c/2*t*t*t*t*t + b
    t -= 2
    return c/2*(t*t*t*t*t + 2) + b
  },

  easeInSine: function (t, b, c, d) {
    return -c * Math.cos(t/d * ( Math.PI/2)) + c + b
  },

  easeOutSine: function (t, b, c, d) {
    return c * Math.sin(t/d * ( Math.PI/2)) + b
  },

  easeInOutSine: function (t, b, c, d) {
    return -c/2 * (  cos( Math.PI*t/d) - 1) + b
  },

  easeInExpo: function (t, b, c, d) {
    return c * Math.pow( 2, 10 * (t/d - 1) ) + b
  },

  easeOutExpo: function (t, b, c, d) {
    return c * ( - Math.pow( 2, -10 * t/d ) + 1 ) + b
  },

  easeInOutExpo: function (t, b, c, d) {
    t /= d/2
    if (t < 1) return c/2 * Math.pow( 2, 10 * (t - 1) ) + b
    t--
    return c/2 * ( - Math.pow( 2, -10 * t) + 2 ) + b
  },

  easeInCirc: function (t, b, c, d) {
    t /= d
    return -c * ( Math.sqrt(1 - t*t) - 1) + b
  },

  easeOutCirc: function (t, b, c, d) {
    t /= d
    t--
    return c * Math.sqrt(1 - t*t) + b
  },

  easeInOutCirc: function (t, b, c, d) {
    t /= d/2
    if (t < 1) return -c/2 * ( Math.sqrt(1 - t*t) - 1) + b
    t -= 2
    return c/2 * ( Math.sqrt(1 - t*t) + 1) + b
  }
}
},{}],17:[function(require,module,exports){
(function (global){
/*
 * Utility Functions
 * General shared functions and properties
 */

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)

var Utility = {
  // Click event
  clickEvent: $('html').is('.has-touchevents') ? 'touchend' : 'click',

  // Transition end event
  transitionEndEvent: 'transitionend webkitTransitionEnd oTransitionEnd otransitionend',

  // Animation end event
  animationEndEvent: 'animationend webkitAnimationEnd oAnimationEnd oanimationend',

  // Generate a random string
  randomString: function (stringLength) {
    var output = ''
    var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    stringLength = stringLength || 8
    for (var i = 0; i < stringLength; i++) {
      output += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return output
  },

  // Convert an input value (most likely a string) into a primitive, e.g. number, boolean, etc.
  convertToPrimitive: function (input) {
    // Non-string? Just return it straight away
    if (typeof input !== 'string') return input

    // Trim any whitespace
    input = (input + '').trim()

    // Number
    if (/^\-?(?:\d*[\.\,])*\d*(?:[eE](?:\-?\d+)?)?$/.test(input)) {
      return parseFloat(input)
    }

    // Boolean: true
    if (/^(true|1)$/.test(input)) {
      return true

    // NaN
    } else if (/^NaN$/.test(input)) {
      return NaN

    // undefined
    } else if (/^undefined$/.test(input)) {
      return undefined

    // null
    } else if (/^null$/.test(input)) {
      return null

    // Boolean: false
    } else if (/^(false|0)$/.test(input) || input === '') {
      return false
    }

    // Default to string
    return input
  },

  checkElemAttrForValue: function (elem, attr) {
    var $elem = $(elem)
    var attrValue = $elem.attr(attr)

    // Check non-'data-' prefixed attributes for one if value is undefined
    if (typeof attrValue === 'undefined' && !/^data\-/i.test(attr)) {
      attrValue = $elem.attr('data-' + attr)
    }

    return attrValue
  },

  // Check if the element is or its parents' matches a {String} selector
  // @returns {Boolean}
  checkElemIsOrHasParent: function (elem, selector) {
    return $(elem).is(selector) || $(elem).parents(selector).length > 0
  },

  // Same as above, except returns the elements itself
  // @returns {Mixed} A {jQueryObject} containing the element(s), or {Boolean} false
  getElemIsOrHasParent: function (elem, selector) {
    var $elem = $(elem)
    if ($elem.is(selector)) return $elem

    var $parents = $elem.parents(selector)
    if ($parents.length > 0) return $parents

    return false
  },

  // Add leading zero
  leadingZero: function (input) {
    return (parseInt(input, 10) < 10 ? '0' : '') + input
  },

  // Get the remaining time between two dates
  // @note TimeCount relies on this to output as an object
  // See: http://www.sitepoint.com/build-javascript-countdown-timer-no-dependencies/
  getTimeRemaining: function (endTime, startTime) {
    var t = Date.parse(endTime) - Date.parse(startTime || new Date())
    var seconds = Math.floor((t/1000) % 60)
    var minutes = Math.floor((t/1000/60) % 60)
    var hours = Math.floor((t/(1000*60*60)) % 24)
    var days = Math.floor(t/(1000*60*60*24))
    return {
      'total': t,
      'days': days,
      'hours': hours,
      'minutes': minutes,
      'seconds': seconds
    }
  }
}

module.exports = Utility

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{}],18:[function(require,module,exports){
(function (global){
/*
 * Unilend Watch Scroll
 *
 * Set and manage callbacks to occur on element's scroll top/left value
 *
 * @linter Standard JS (http://standardjs.com/)
 */

// Watch element for scroll left/top and perform callback if:
// -- If element reaches scroll left/top value
// -- If specific child element in element is entered or is visible in viewport
// -- If specific child element in element is left or not visible in viewport

// Dependencies
var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var Bounds = require('ElementBounds')

// requestAnimationFrame polyfill
// See: http://creativejs.com/resources/requestanimationframe/
;(function() {
  var lastTime = 0
  var vendors = ['ms', 'moz', 'webkit', 'o']
  for(var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
    window.requestAnimationFrame = window[vendors[x]+'RequestAnimationFrame']
    window.cancelAnimationFrame = window[vendors[x]+'CancelAnimationFrame']
                               || window[vendors[x]+'CancelRequestAnimationFrame']
  }

  if (!window.requestAnimationFrame)
    window.requestAnimationFrame = function(callback, element) {
      var currTime = new Date().getTime()
      var timeToCall = Math.max(0, 16 - (currTime - lastTime))
      var id = window.setTimeout(function() { callback(currTime + timeToCall) },
        timeToCall)
      lastTime = currTime + timeToCall
      return id
    }

  if (!window.cancelAnimationFrame)
    window.cancelAnimationFrame = function(id) {
      clearTimeout(id)
    }
}())

/*
 * WatchScroll
 */
var WatchScroll = {
  /*
   * Actions to test Watcher elements and targets with
   * @note See Watcher.checkTargetForAction() to see how these get applied
   * @property
   */
  actions: {
    // Checks to see if the target is outside the element
    outside: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.withinBounds(elemBounds)
      elemBounds.showViz()
      targetBounds.showViz()
      if (!state) return 'outside'
    },

    // Checks to see if the target is before the element (X axis)
    before: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.coords[2] < elemBounds.coords[0]
      elemBounds.showViz()
      targetBounds.showViz()
      if (state) return 'before'
    },

    // Checks to see if the target is after the element (X axis)
    after: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.coords[0] > elemBounds.coords[2]
      elemBounds.showViz()
      targetBounds.showViz()
      if (state) return 'after'
    },

    // Checks to see if the target is above the element (Y axis)
    above: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      // target.Y2 < elem.Y1
      var state = targetBounds.coords[3] < elemBounds.coords[1]
      elemBounds.showViz()
      targetBounds.showViz()
      if (state) return 'above'
    },

    // Checks to see if the target is below the element (Y axis)
    below: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      // target.Y1 > elem.Y2
      var state = targetBounds.coords[1] > elemBounds.coords[3]
      if (state) return 'below'
    },

    // Checks if the target is past the element (Y axis)
    past: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      // target.Y1 > elem.Y1
      var state = targetBounds.coords[1] > elemBounds.coords[1]
      elemBounds.showViz()
      targetBounds.showViz()
      if (state) return 'past'
    },

    // Checks to see if the target is within the element
    within: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.withinBounds(elemBounds)
      if (state) return 'within'
    },

    // Checks to see if the target is in top half of the element
    withinTopHalf: function (params) {
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem).scale(1, 0.5)
      var targetBounds = new Bounds().setBoundsFromElem(this)
      var state = targetBounds.withinBounds(elemBounds)
      if (state) return 'withintophalf'
    },

    // Checks to see if target is in the middle of the element
    withinMiddle: function (params) {
      // Get the bounds of all
      var elemBounds = new Bounds().setBoundsFromElem(params.Watcher.elem)
      var targetBounds = new Bounds().setBoundsFromElem(this)

      // Get middle of elem
      var middleY1 = (elemBounds.getHeight() * 0.5) - 1
      var middleY2 = (elemBounds.getHeight() * 0.5)
      var middleBounds = new Bounds(elemBounds.coords[0], middleY1, elemBounds.coords[2], middleY2)

      // Is target within middle?
      var state = targetBounds.withinBounds(middleBounds)
      if (state) return 'withinmiddle'
    }
  },

  /*
   * WatchScroll.Watcher
   * Watches an element with a list of listeners and targets
   * @class
   * @param elem {String | HTMLElement} The element to watch scroll positions
   * @param options {Object} The options to configure the watcher
   */
  Watcher: function (elem, options) {
    var self = this

    /*
     * Properties
     */
    self.$elem = $(elem) // jQuery
    self.elem = self.$elem[0] // Normal HTMLElement
    self.listeners = [] // List of listeners

    /*
     * Options
     */
    self.options = $.extend({
      // Nothing yet
    }, options)

    /*
     * Methods
     */
    // Add a listener to watch a target element (or collection of elements)
    self.watch = function (target, action, callback) {
      // Needs a valid target, action and callback
      if (typeof target !== 'object' && typeof target !== 'string') return
      if (typeof action !== 'string' && typeof action !== 'function') return
      // if (typeof callback !== 'function') return

      // Create the WatchScrollListener
      var watchScrollListener = new WatchScroll.Listener(target, action, callback)
      watchScrollListener.WatchScrollWatcher = self

      // @debug console.log('WatchScroll.watch', target, action)

      // Fire any relevant actions on the newly watched target
      watchScrollListener.$target.each( function (i, target) {
        for (var i = 0; i < watchScrollListener.action.length; i++) {
          var doneAction = self.checkTargetForAction(target, watchScrollListener.action[i], watchScrollListener.callback)
        }
      })

      // Enable watching
      if (watchScrollListener) self.listeners.push(watchScrollListener)

      // You can chain more watchers to the instance
      return self
    }

    // Get the bounds of an element
    self.getBounds = function (target) {
      var targetBounds = new Bounds().setBoundsFromElem(target).coords
      return targetBounds
    }

    // Check if a space (denoted either by an element, or by 2 sets of x/y co-ords) is visible within the element
    self.isVisible = function (target) {
      var elemBounds = new Bounds().setBoundsFromElem(self.elem)
      var targetBounds = new Bounds().setBoundsFromElem(target)
      var visible = targetBounds.withinBounds(elemBounds)
      return visible && $(target).is(':visible')
    }

    // Check all watchScrollListeners and determines if targets can be actioned upon
    self.checkListeners = function () {
      var targetsVisible = []

      // Iterate over all listeners and fire callback depending on target's state (enter/leave/visible/hidden)
      for ( var x in self.listeners ) {
        var listener = self.listeners[x]

        // Iterate through each target
        listener.$target.each( function (i, target) {
          var isVisible = self.isVisible(target)

          // Store the isVisible to apply as wasVisible after all listeners have been processed
          targetsVisible.push({
            target: target,
            wasVisible: isVisible
          })

          // Iterate through each action
          for ( var y in listener.action ) {
            self.checkTargetForAction(target, listener.action[y], listener.callback)
          }
        });
      }

      // Iterate over all targets and apply their isVisible value to wasVisible
      for ( x in targetsVisible ) {
        targetsVisible[x].target.wasVisible = targetsVisible[x].wasVisible
      }
    }

    // Check single target for state
    self.getTargetState = function (target) {
      var $target = $(target)
      target = $target[0]
      var state = []

      // Visibility
      var wasVisible = target.wasVisible || false
      var isVisible = self.isVisible(target)

      // Enter
      if ( !wasVisible && isVisible ) {
        state.push('enter')

      // Visible
      } else if ( wasVisible && isVisible ) {
        state.push('visible')

      // Leave
      } else if ( wasVisible && !isVisible ) {
        state.push('leave')

      // Hidden
      } else if ( !wasVisible && !isVisible ) {
        state.push('hidden')
      }

      // @debug console.log( 'WatchScroll.getTargetState', wasVisible, isVisible, target )

      return state.join(' ')
    }

    // Fire callback if target matches action
    //
    // Valid actions:
    //  -- positionTop>50       => target.positionTop > 50
    //  -- scrollTop><50:100    => target.scrollTop > 50 && target.scrollTop < 100
    //  -- offsetTop<=>50:100   => target.offsetTop <= 0 && target.offsetTop >= 100
    //  -- enter                => target.isVisible && !target.wasVisible
    //
    // See switch control blocks below for more expressions and state keywords
    self.checkTargetForAction = function (target, action, callback) {
      var doAction = false
      var $target = $(target)
      var target = $target[0]
      var state

      // Custom action
      if (typeof action === 'function') {
        // Fire the action to see if it applies
        doAction = action.apply(target, [{
          Watcher: self,
          target: target,
          callback: callback
        }])

        // Successful action met
        if (doAction) {
          // Fire the callback
          if (typeof callback === 'function') callback.apply(target, [doAction])

          // Trigger actions for any other things watching
          // If your custom action returns a string, it'll trigger 'watchscroll-action-{returned string}'
          if (typeof doAction === 'string') $(target).trigger('watchscroll-action-' + doAction, [self])
        }

        return doAction
      }

      // Action is a string
      // Ensure lowercase
      action = action.toLowerCase()

      // Get target position
      if (/^((position|offset|scroll)top)/.test(action)) {
        // Break action into components, e.g. scrollTop>50 => scrolltop, >, 50
        var prop = action.replace(/^((position|offset|scroll)top).*$/, '$1').trim()
        var exp = action.replace(/^[\w\s]+([\<\>\=]+).*/, '$1').trim()
        var value = action.replace(/^[\w\s]+[\<\>\=]+(\s*[\d\-\.\:]+)$/, '$1').trim()
        var checkValue

        // Split value if it is a range (i.e. has a `:` separating two numbers: `120:500`)
        if (/\-?\d+(\.[\d+])?:\-?\d+(\.[\d+])?/.test(value)) {
          value = value.split(':')
          value[0] = parseFloat(value[0])
          value[1] = parseFloat(value[1])
        } else {
          value = parseFloat(value)
        }

        // Get the value to check based on prop
        switch (prop.toLowerCase()) {
          case 'positiontop':
            checkValue = $target.position().top
            break;

          case 'offsettop':
            checkValue = $target.offset().top
            break;

          case 'scrolltop':
            checkValue = $target.scrollTop()
            break;
        }

        // @debug console.log( action, prop, exp, value, checkValue )

        // Compare values
        switch (exp) {
          // eq
          case '=':
          case '==':
          case '===':
            if ( checkValue == value ) {
              doAction = true
            }
            break;

          // ne
          case '!=':
          case '!==':
            if ( checkValue == value ) {
              doAction = true
            }
            break;

          // gt
          case '>':
            if ( checkValue > value ) {
              doAction = true
            }
            break;

          // gte
          case '>=':
            if ( checkValue >= value ) {
              doAction = true
            }
            break;

          // lt
          case '<':
            if ( checkValue < value ) {
              doAction = true
            }
            break;

          // lte
          case '<=':
            if ( checkValue <= value ) {
              doAction = true
            }
            break;

          // outside range
          case '<>':
            if ( value instanceof Array && (checkValue < value[0] && checkValue > value[1]) ) {
              doAction = true
            }
            break;

          // outside range (including min:max)
          case '<=>':
            if ( value instanceof Array && (checkValue <= value[0] && checkValue >= value[1]) ) {
              doAction = true
            }
            break;

          // inside range
          case '><':
            if ( value instanceof Array && (checkValue > value[0] && checkValue < value[1]) ) {
              doAction = true
            }
            break;

          // Inside range (including min:max)
          case '>=<':
            if ( value instanceof Array && (checkValue >= value[0] && checkValue <= value[1]) ) {
              doAction = true
            }
            break;
        }

      // Keyword actions representing state: enter, leave, visible, hidden
      } else {
        state = self.getTargetState(target)
        if (state.match(action)) {
          doAction = true
        }
      }

      // @debug console.log(state, doAction, target, $target)
      // @debug console.log( 'WatchScroll.Watcher.checkTargetForAction:', action, target )

      if (doAction) {
        doAction = action
        // @debug console.log( ' --> ' + doAction )
        if (typeof callback === 'function') {
          callback.apply(target)
        }

        // Trigger actions for any other things watching
        if (typeof doAction === 'string') $(target).trigger('watchscroll-action-' + doAction, [self])
      }

      return doAction
    }


    /*
     * Events
     */
    self.$elem.on('scroll', function ( event ) {
      // Let the browser determine best time to animate
      requestAnimationFrame(self.checkListeners)

      // @debug console.log('event.scroll')
      self.checkListeners()
    })

    // @debug console.log(self.elem)
    return self
  },

  /*
   * WatchScrollListener
   * @class
   */
  Listener: function (target, action, callback) {
    var self = this

    // Needs a target, action and callback
    if (typeof target !== 'object' && typeof target !== 'string') return false
    if (typeof action !== 'string' && typeof action !== 'function') return false

    // @debug console.log('added WatchScrollListener', target, action)

    /*
     * Properties
     */
    self.WatchScrollWatcher // Parent WatchScroll Watcher, for reference if needed
    self.$target = $(target) // The target(s)

    // Convert action to array of action(s)
    if (typeof action === 'string') {
      self.action = /\s/.test(action) ? action.split(/\s+/) : [action]
    } else {
      self.action = [action]
    }

    self.callback = callback
    self.hasCallback = (typeof callback === 'function')
    if (self.hasCallback) self.callback = callback

    /*
     * Methods
     */
    // Do callback
    self.doCallback = function () {
      if (!self.hasCallback) return

      self.$target.each( function (i, target) {
        // @debug console.log( 'WatchScrollListener', target, self.action )
        self.callback.apply(target)
      })
    }

    return self
  }
}

module.exports = WatchScroll

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"ElementBounds":14}],19:[function(require,module,exports){
(function (global){
/*
 * Dictionary Shortcut
 */

var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null)
var Dictionary = require('Dictionary')
var UNILEND_LANG = require('../../../lang/Unilend.lang.json')
var __ = new Dictionary(UNILEND_LANG, $('html').attr('lang') || 'fr')

module.exports = __

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"../../../lang/Unilend.lang.json":22,"Dictionary":12}],20:[function(require,module,exports){
(function (global){
/*
 * Unilend JS
 *
 * @linter Standard JS (http://standardjs.com/)
 */

// @TODO emprunter sim functionality
// @TODO AutoComplete needs hooked up to AJAX
// @TODO Sortable may need AJAX functionality
// @TODO FileAttach may need AJAX functionality

// Dependencies
var $ = (typeof window !== "undefined" ? window['jQuery'] : typeof global !== "undefined" ? global['jQuery'] : null) // Gets the global (see package.json)
var videojs = (typeof window !== "undefined" ? window['videojs'] : typeof global !== "undefined" ? global['videojs'] : null) // Gets the global (see package.json)
var svg4everybody = require('svg4everybody')
var Swiper = (typeof window !== "undefined" ? window['Swiper'] : typeof global !== "undefined" ? global['Swiper'] : null)
var Iban = require('iban')

// Lib
var Utility = require('Utility')
var __ = require('__')
var Tween = require('Tween')
var ElementBounds = require('ElementBounds')

// Components & behaviours
var AutoComplete = require('AutoComplete')
var WatchScroll = require('WatchScroll')
var TextCount = require('TextCount')
var TimeCount = require('TimeCount')
var Sortable = require('Sortable')
var PasswordCheck = require('PasswordCheck')
var FileAttach = require('FileAttach')
var FormValidation = require('FormValidation')
var DashboardPanel = require('DashboardPanel')
// var Sticky = require('Sticky') // @note unfinished

//
$(document).ready(function ($) {
  // Main vars/elements
  var $doc = $(document)
  var $html = $('html')
  var $win = $(window)
  var $siteHeader = $('.site-header')
  var $siteFooter = $('.site-footer')

  // Remove HTML
  $html.removeClass('no-js')

  // TWBS setup
  // $.support.transition = false
  // Bootstrap Tooltips
   $('[data-toggle="tooltip"]').tooltip()

  // Breakpoints
  // -- Devices
  var breakpoints = {
    'mobile-p': [0, 599],
    'mobile-l': [600, 799],
    'tablet-p': [800, 1023],
    'tablet-l': [1024, 1299],
    'laptop':   [1300, 1599],
    'desktop':  [1600, 99999]
  }

  // -- Device groups
  breakpoints.mobile = [0, breakpoints['mobile-l'][1]]
  breakpoints.tablet = [breakpoints['tablet-p'][0], breakpoints['tablet-l'][1]]
  breakpoints.computer = [breakpoints['laptop'][0], breakpoints['desktop'][1]]

  // -- Grids
  breakpoints.xs = breakpoints['mobile-p']
  breakpoints.sm = [breakpoints['mobile-l'][0], breakpoints['tablet-p'][1]]
  breakpoints.md = [breakpoints['tablet-l'][0], breakpoints['laptop'][1]]
  breakpoints.lg = breakpoints['desktop']

  // Track the current breakpoints (also updated in updateWindow())
  var currentBreakpoint = getActiveBreakpoints()

  // VideoJS
  // Running a modified version to customise the placement of items in the control bar
  videojs.options.flash.swf = null // @TODO needs correct link '/js/vendor/videojs/video-js.swf'

  // siteSearch autocomplete
  var siteSearchAutoComplete = new AutoComplete('.site-header .site-search-input', {
    // @TODO eventually when AJAX is connected, the URL will go here
    // ajaxUrl: '',
    target: '.site-header .site-search .autocomplete'
  })

  // Site Search
  var siteSearchTimeout = 0

  // -- Events
  $doc
    // Activate/focus .site-search-input
    .on(Utility.clickEvent + ' active focus keydown', '.site-search-input', function (event) {
      openSiteSearch()
    })
    // Hover over .site-search .autocomplete
    .on('mouseenter mouseover', '.site-search .autocomplete', function (event) {
      openSiteSearch()
    })

    // Dismiss site search after blur
    .on('keydown', '.site-search-input', function (event) {
      // @debug console.log('keyup', '.site-search-input')
      // Dismiss
      if (event.which === 27) {
        closeSiteSearch(0)
        $(this).blur()
      }
    })
    .on('blur', '.site-search-input, .site-search .autocomplete-results a', function (event) {
      // @debug console.log('blur', '.site-search-input')
      closeSiteSearch(200)
    })
    // @debug
    // .on('mouseleave', '.site-header .site-search', function (event) {
    //   console.log('mouseleave', '.site-search')

    //   // Don't dismiss
    //   if ($('.site-header .site-search-input').is(':focus, :active')) {
    //     return
    //   }

    //   closeSiteSearch()
    // })

    // Stop site search dismissing when hover in autocomplete
    .on('mouseenter mouseover', '.site-search .autocomplete', function (event) {
      // @debug console.log('mouseenter mouseover', '.site-header .site-search .autocomplete a')
      cancelCloseSiteSearch()
    })
    // Stop site search dismissing when focus/active links in autocomplete
    .on('keydown focus active', '.site-search .autocomplete a', function (event) {
      // @debug console.log('keydown focus active', '.site-header .site-search .autocomplete a')
      cancelCloseSiteSearch()
    })

  // -- Methods
  function openSiteSearch () {
    // @debug console.log('openSiteSearch')
    cancelCloseSiteSearch()
    $html.addClass('ui-site-search-open')
  }

  function closeSiteSearch (timeout) {
    // @debug console.log('closeSiteSearch', timeout)

    // Defaults to time out after .5s
    if (typeof timeout === 'undefined') timeout = 500

    siteSearchTimeout = setTimeout(function () {
      $html.removeClass('ui-site-search-open')

      // Hide the autocomplete
      siteSearchAutoComplete.hide()
    }, timeout)
  }

  function cancelCloseSiteSearch () {
    // @debug console.log('cancelCloseSiteSearch')
    clearTimeout(siteSearchTimeout)
  }

  /*
   * Site Mobile Menu
   */
  // Show the site mobile menu
  $doc.on(Utility.clickEvent, '.site-mobile-menu-open', function (event) {
    event.preventDefault()
    openSiteMobileMenu()
  })

  // Close the site mobile menu
  $doc.on(Utility.clickEvent, '.site-mobile-menu-close', function (event) {
    event.preventDefault()
    closeSiteMobileMenu()
  })

  // At end of opening animation
  $doc.on(Utility.animationEndEvent, '.ui-site-mobile-menu-opening', function (event) {
    showSiteMobileMenu()
  })

  // At end of closing animation
  $doc.on(Utility.animationEndEvent, '.ui-site-mobile-menu-closing', function (event) {
    hideSiteMobileMenu()
  })

  function openSiteMobileMenu () {
    // @debug console.log('openSiteMobileMenu')
    if (isIE(9) || isIE('<9')) return showSiteMobileMenu()
    if (!$html.is('.ui-site-mobile-menu-open, .ui-site-mobile-menu-opening')) {
      $html.removeClass('ui-site-mobile-menu-closing').addClass('ui-site-mobile-menu-opening')
    }
  }

  function closeSiteMobileMenu () {
    if (isIE(9) || isIE('<9')) return hideSiteMobileMenu()
    // @debug console.log('closeSiteMobileMenu')
    $html.removeClass('ui-site-mobile-menu-opening ui-site-mobile-menu-open').addClass('ui-site-mobile-menu-closing')
  }

  function showSiteMobileMenu () {
    // @debug console.log('showSiteMobileMenu')
    $html.addClass('ui-site-mobile-menu-open').removeClass('ui-site-mobile-menu-opening ui-site-mobile-menu-closing')

    // ARIA stuff
    $('.site-mobile-menu').removeAttr('aria-hidden')
    $('.site-mobile-menu [tabindex]').attr('tabindex', 1)
  }

  function hideSiteMobileMenu () {
    // @debug console.log('hideSiteMobileMenu')
    $html.removeClass('ui-site-mobile-menu-opening ui-site-mobile-menu-closing ui-site-mobile-menu-open')

    // ARIA stuff
    $('.site-mobile-menu').attr('aria-hidden', 'true')
    $('.site-mobile-menu [tabindex]').attr('tabindex', -1)
  }

  /*
   * Site Mobile Search
   */

  // Click button search
  $doc.on(Utility.clickEvent, '.site-mobile-search-toggle', function (event) {
    event.preventDefault()
    if (!$html.is('.ui-site-mobile-search-open')) {
      openSiteMobileSearch()
    } else {
      closeSiteMobileSearch()
    }
  })

  // Focus/activate input
  $doc.on('focus active', '.site-mobile-search-input', function (event) {
    // @debug console.log('focus active .site-mobile-search-input')
    openSiteMobileSearch()
  })

  // Blur input
  // $doc.on('blur', '.site-mobile-search-input', function (event) {
  //   // @debug console.log('blur site-mobile-search-input')
  //   closeSiteMobileSearch()
  // })

  function openSiteMobileSearch () {
    // @debug console.log('openSiteMobileSearch')
    openSiteMobileMenu()
    $html.addClass('ui-site-mobile-search-open')
  }

  function closeSiteMobileSearch () {
    $html.removeClass('ui-site-mobile-search-open')
  }

  /*
   * Open search (auto-detects which)
   */
  function openSearch() {
    // Mobile site search
    if (/xs|sm/.test(currentBreakpoint)) {
      // @debug console.log('openSiteMobileSearch')
      openSiteMobileSearch()
      $('.site-mobile-search-input').focus()

    // Regular site search
    } else {
      $('.site-search-input').focus()
    }
  }

  // Open the site-search from a different button
  $doc.on('click', '.ui-open-site-search', function (event) {
    event.preventDefault()
    openSearch()
  })

  /*
   * FancyBox
   */
  $('.fancybox').fancybox()
  $('.fancybox-media').each(function (i, elem) {
    var $elem = $(elem)
    if ($elem.is('.fancybox-embed-videojs')) {
      $elem.fancybox({
        padding: 0,
        margin: 0,
        autoSize: true,
        autoCenter: true,
        content: '<div class="fancybox-video"><video id="fancybox-videojs" class="video-js" autoplay controls preload="auto" data-setup=\'{ "techOrder": ["youtube"], "sources": [{ "type": "video/youtube", "src": "' + $elem.attr('href') + '" }], "inactivityTimeout": 0 }\'></video></div>',
        beforeShow: function () {
          $.fancybox.showLoading()
          $('.fancybox-overlay').addClass('fancybox-loading')
        },
        // Assign video player functionality
        afterShow: function () {
          // Video not assigned yet
          if (!videojs.getPlayers().hasOwnProperty('fancybox-video')) {
            videojs('#fancybox-videojs', {}, function () {
              var videoPlayer = this

              // Set video width
              var videoWidth = window.innerWidth * 0.7
              if (videoWidth < 280) videoWidth = 280
              if (videoWidth > 1980) videoWidth = 1980
              videoPlayer.width(videoWidth)

              // Update the fancybox width
              $.fancybox.update()
              $.fancybox.hideLoading()
              setTimeout(function () {
                $('.fancybox-overlay').removeClass('fancybox-loading')
              }, 200)
            })
          } else {
            $.fancybox.update()
            $.fancybox.hideLoading()
            setTimeout(function () {
              $('.fancybox-overlay').removeClass('fancybox-loading')
            }, 200)
          }
        },
        // Remove video player on close
        afterClose: function () {
          videojs.getPlayers()['fancybox-videojs'].dispose()
        }
      })
    } else {
      $elem.fancybox({
        helpers: {
          'media': {}
        }
      })
    }
  })

  /*
   * Swiper
   */
  $('.swiper-container').each(function (i, elem) {
    var $elem = $(elem)
    var swiperOptions = {
      direction: $elem.attr('data-swiper-direction') || 'horizontal',
      loop: $elem.attr('data-swiper-loop') === 'true',
      effect: $elem.attr('data-swiper-effect') || 'fade',
      speed: parseInt($elem.attr('data-swiper-speed'), 10) || 250,
      autoplay: parseInt($elem.attr('data-swiper-autoplay'), 10) || 5000,
      // ARIA keyboard functionality
      a11y: $elem.attr('data-swiper-aria') === 'true'
    }

    // Fade / Crossfade
    if (swiperOptions.effect === 'fade') {
      swiperOptions.fade = {
        crossFade: $elem.attr('data-swiper-crossfade') === 'true'
      }
    }

    // Dynamically test if has pagination
    if ($elem.find('.swiper-custom-pagination').length > 0 && $elem.find('.swiper-custom-pagination > *').length > 0) {
      swiperOptions.paginationType = 'custom'
    }

    var elemSwiper = new Swiper(elem, swiperOptions)
    // console.log(elemSwiper)

    // Add event to hook up custom pagination to appropriate slide
    if (swiperOptions.paginationType === 'custom') {
      // Hook into sliderMove event to update custom pagination
      elemSwiper.on('slideChangeStart', function () {
        // Unactive any active pagination items
        $elem.find('.swiper-custom-pagination li.active').removeClass('active')

        // Activate the current pagination item
        $elem.find('.swiper-custom-pagination li:eq(' + elemSwiper.activeIndex + ')').addClass('active')

        // console.log('sliderMove', elemSwiper.activeIndex)
      })

      // Connect user interaction with custom pagination
      $elem.find('.swiper-custom-pagination li').on('click', function (event) {
        var $elem = $(this).parents('.swiper-container')
        var $target = $(this)
        var swiper = $elem[0].swiper
        var newSlideIndex = $elem.find('.swiper-custom-pagination li').index($target)

        event.preventDefault()
        swiper.pauseAutoplay()
        swiper.slideTo(newSlideIndex)
      })
    }

    // Specific swipers
    // -- Homepage Acquisition Video Hero
    if ($elem.is('#homeacq-video-hero-swiper')) {
      elemSwiper.on('slideChangeStart', function () {
        var emprunterName = $elem.find('.swiper-slide:eq(' + elemSwiper.activeIndex + ')').attr('data-emprunter-name')
        var preterName = $elem.find('.swiper-slide:eq(' + elemSwiper.activeIndex + ')').attr('data-preter-name')
        if (emprunterName) $elem.parents('.cta-video-hero').find('.ui-emprunter-name').text(emprunterName)
        if (preterName) $elem.parents('.cta-video-hero').find('.ui-preter-name').text(preterName)
      })
    }
  })


  // @debug remove for production
  $doc
    .on(Utility.clickEvent, '#set-lang-en', function (event) {
      event.preventDefault()
      $html.attr('lang', 'en')
      __.defaultLang = 'en'
    })
    .on(Utility.clickEvent, '#set-lang-en-gb', function (event) {
      event.preventDefault()
      $html.attr('lang', 'en-gb')
      __.defaultLang = 'en-gb'
    })
    .on(Utility.clickEvent, '#set-lang-fr', function (event) {
      event.preventDefault()
      $html.attr('lang', 'fr')
      __.defaultLang = 'fr'
    })
    .on(Utility.clickEvent, '#set-lang-es', function (event) {
      event.preventDefault()
      $html.attr('lang', 'es')
      __.defaultLang = 'es'
    })
    .on(Utility.clickEvent, '#restart-text-counters', function (event) {
      event.preventDefault()
      $('.ui-text-count').each(function (i, elem) {
        elem.TextCount.resetCount()
        elem.TextCount.startCount()
      })
    })

  /*
   * Time counters
   */
  $doc
    // Basic project time counter
    .on('TimeCount:update', '.ui-time-counting', function (event, elemTimeCount, timeRemaining) {
      var $elem = $(this)
      var outputTime

      // @debug console.log(timeRemaining)

      if (timeRemaining.days > 2) {
        outputTime = (timeRemaining.days + Math.ceil(timeRemaining.hours / 24)) + ' ' + __.__('days', 'timeCountDays') + ' ' + __.__('remaining', 'timeCountRemaining')
      } else {
        // Expired
        if (timeRemaining.seconds < 0) {
          outputTime = __.__('Project expired', 'projectPeriodExpired')

        // Countdown
        } else {
          outputTime = Utility.leadingZero(timeRemaining.hours + (24 * timeRemaining.days)) + ':' + Utility.leadingZero(timeRemaining.minutes) + ':' + Utility.leadingZero(timeRemaining.seconds)
        }
      }

      // Update counter
      $elem.text(outputTime)
    })

    // Project list time counts completed
    .on('TimeCount:completed', '.project-list-item .ui-time-count', function () {
      $(this).parents('.project-list-item').addClass('ui-project-expired')
      $(this).text(__.__('Project expired', 'projectListItemPeriodExpired'))
    })

    // Project single time count completed
    .on('TimeCount:completed', '.project-single .ui-time-count', function () {
      $(this).parents('.project-single').addClass('ui-project-expired')
      $(this).text(__.__('Project expired', 'projectSinglePeriodExpired'))
    })

  /*
   * Watch Scroll
   */
  // Window scroll watcher
  var watchWindow = new WatchScroll.Watcher(window)
    // Fix site nav
    .watch(window, 'scrollTop>50', function () {
      $html.addClass('ui-site-header-fixed')
    })
    // Unfix site nav
    .watch(window, 'scrollTop<=50', function () {
      $html.removeClass('ui-site-header-fixed')
    })
    // Start text counters
    .watch('.ui-text-count', 'enter', function () {
      if (this.hasOwnProperty('TextCount')) {
        if (!this.TextCount.started()) this.TextCount.startCount()
      }
    })

  // Dynamic watchers (single)
  // @note if you need to add more than one action, I suggest doing it via JS
  $('[data-watchscroll-action]').each(function (i, elem) {
    var $elem = $(elem)
    var action = {
      action: $elem.attr('data-watchscroll-action'),
      callback: $elem.attr('data-watchscroll-callback'),
      target: $elem.attr('data-watchscroll-target')
    }

    // Detect which action and callback to fire
    watchWindow.watch(elem, $elem.attr('data-watchscroll-action'), function () {
      watchScrollCallback.apply(elem, [action])
    })
  })

  // Basic WatchScroll callback methods
  function watchScrollCallback (action) {
    var $elem = $(this)

    // e.g. `addClass:ui-visible`
    var handle = action.callback
    var target = action.target || $elem[0]
    var method = handle
    var value
    if (!handle) return

    // Split to get other values
    if (/\:/.test(handle)) {
      handle = handle.split(':')
      method = handle[0]
      value = handle[1]
    }

    // Get the target
    $target = $(target)

    // @debug console.log('watchScrollCallback', this, method, value);

    // Handle different methods
    switch (method.toLowerCase()) {
      // addclass:class-to-add
      case 'addclass':
        $target.addClass(value)
        break

      // removeclass:class-to-remove
      case 'removeclass':
        $target.removeClass(value)
        break
    }
  }

  /*
   * WatchScroll Nav: If item is visible (via WatchScroll action `enter`) then make the navigation item active
   */
  $('[data-watchscroll-nav]').each(function (i, elem) {
    watchWindow.watch(elem, WatchScroll.actions.withinMiddle)
  })
  $doc.on('watchscroll-action-withinmiddle', '[data-watchscroll-nav]', function () {
    var $navLinks = $('.nav li:not(".active") a[href="#' + $(this).attr('id') + '"]')
    $navLinks.each(function (i, elem) {
      var $elem = $(elem)
      var $navItem = $elem.parents('li').first()
      if (!$navItem.is('.active')) {
        $elem.parents('.nav').first().find('li').removeClass('active').filter($navItem).addClass('active')
      }
    })
  })

  /*
   * Fixed project single menu
   */
  var projectSingleNavOffsetTop
  if ($('.project-single-menu').length > 0) {
    projectSingleNavOffsetTop = $('.project-single-nav').first().offset().top - (parseInt($('.site-header').height(), 10) * 0.5)
    watchWindow
      .watch(window, function (params) {
        // @debug console.log($win.scrollTop() >= projectSingleNavOffsetTop)
        if ($win.scrollTop() >= projectSingleNavOffsetTop) {
          $html.addClass('ui-project-single-menu-fixed')
        } else {
          $html.removeClass('ui-project-single-menu-fixed')
        }
      })
  }

  /*
   * Progress tabs
   */
  // Any tabs areas with `.ui-tabs-progress` class will add a `.complete` class to the tabs before
  $doc.on('shown.bs.tab', '.tabs.ui-tabs-progress', function (event) {
    var $target = $(event.target)
    var $tab = $('.nav a[role="tab"][href="' + $target.attr('href') + '"]').first()
    var $nav = $tab.parents('.nav')
    var $tabs = $nav.find('a[role="tab"]')
    var tabIndex = $tabs.index($tab)

    if (tabIndex >= 0) {
      $tabs.filter(':gt('+tabIndex+')').parents('li').removeClass('active complete')
      $tabs.filter(':lt('+tabIndex+')').parents('li').removeClass('active').addClass('complete')
      $tab.parents('li').removeClass('complete').addClass('active')
    }
  })

  // Validate any groups/fields within the tabbed area before going on to the next stage
  $doc.on('show.bs.tab', '.tabs.ui-tabs-progress [role="tab"]', function (event) {
    var $form = Utility.getElemIsOrHasParent(event.target, 'form').first()
    var $nextTab = $($(event.target).attr('href'))
    var $currentTab = $form.find('[role="tabpanel"].active').first()

    // console.log($form, $currentTab)

    // Validate the form within the current tab before continuing
    if ($currentTab.find('[data-formvalidation]').length > 0) {
      var fa = $currentTab.find('[data-formvalidation]').first()[0].FormValidation
      var formValidation = fa.validate()
      console.log(formValidation)

      // Validation Errors: prevent going to the next tab
      if (formValidation.erroredFields.length > 0) {
        event.preventDefault()
        event.stopPropagation()
        scrollTo(fa.$notifications)
        return false
      }
    }
  })

  /*
   * Emprunter Sim
   */
  $doc
    .on('shown.bs.tab', '.emprunter-sim', function () {
      console.log('shown tab')
    })
    // Step 1
    .on('FormValidation:validate:error', '#esim1', function () {
      // Hide the continue button
      $('.emprunter-sim').removeClass('ui-emprunter-sim-estimate-show')
    })
    .on('FormValidation:validate:success', '#esim1', function () {
      // Show the continue button
      $('.emprunter-sim').addClass('ui-emprunter-sim-estimate-show')
    })
    .on('change', 'form.emprunter-sim', function (event) {
      console.log(event.type, event.target)
    })
    // Step 2
    // .on('FormValidation:validate:error', '#esim2', function () {
    //   // Hide the submit button
    //   $('.emprunter-sim').removeClass('ui-emprunter-sim-step-2')
    // })
    // .on('FormValidation:validate:success', '#esim2', function () {
    //   // Show the submit button
    //   $('.emprunter-sim').removeClass('ui-emprunter-sim-step-1').addClass('ui-emprunter-sim-step-2')
    // })

  /*
   * Project List
   */
  // Set original order for each list
  $('.project-list').each(function (i, elem) {
    var $elem = $(elem)
    var $items = $elem.find('.project-list-item')

    $items.each(function (j, item) {
      $(item).attr('data-original-order', j)
    })
  })

  // Reorder project list depending on filtered column and sort direction
  $doc.on(Utility.clickEvent, '.project-list-filter[data-sort-by]', function (event) {
    var $elem = $(this)
    var $projectList = $elem.parents('.project-list')
    var $filters = $projectList.find('.project-list-filter')
    var $list = $projectList.find('.project-list-items')
    var $items = $list.find('.project-list-item')
    var sortColumn = false
    var sortDirection = false
    event.preventDefault()

    // Get column to sort by
    sortColumn = $elem.attr('data-sort-by')

    // Get direction to sort by
    if ($elem.is('.ui-project-list-sort-asc')) {
      sortDirection = 'desc'
    } else {
      sortDirection = 'asc'
    }

    // Error if invalid values
    if (!sortColumn || !sortDirection) return

    // Reset all sorting filters
    $filters.removeClass('ui-project-list-sort-asc ui-project-list-sort-desc')

    // Sorting
    switch (sortDirection) {
      case 'asc':
        $items.sort(function (a, b) {
          a = parseFloat($(a).attr('data-sort-' + sortColumn))
          b = parseFloat($(b).attr('data-sort-' + sortColumn))
          switch (a > b) {
            case true:
              return 1
            case false:
              return -1
            default:
              return 0
          }
        })
        break

      case 'desc':
        $items.sort(function (a, b) {
          a = parseFloat($(a).attr('data-sort-' + sortColumn))
          b = parseFloat($(b).attr('data-sort-' + sortColumn))
          switch (a < b) {
            case true:
              return 1
            case false:
              return -1
            default:
              return 0
          }
        })
        break
    }

    // Set sorted column to sort direction class
    $elem.addClass('ui-project-list-sort-' + sortDirection)

    // Change the DOM order of items
    $items.detach().appendTo($list)
  })

  /*
   * Test for IE
   */
  function isIE (version) {
    var versionNum = ~~(version + ''.replace(/\D+/g, ''))
    if (/^\</.test(version)) {
      version = 'lt-ie' + versionNum
    } else {
      version = 'ie' + versionNum
    }
    return $html.is('.' + version)
  }

  /*
   * I hate IE
   */
  if (isIE(9)) {
    // Specific fixes for IE
    $('.project-list-item .project-list-item-category').each(function (i, item) {
      $(this).wrapInner('<div style="width: 100%; height: 100%; position: relative"></div>')
    })
  }

  /*
   * Responsive
   */
  // Get breakpoints
  // @method getActiveBreakpoints
  // @returns {String}
  function getActiveBreakpoints() {
    var width = window.innerWidth;
    var bp = []
    for (var x in breakpoints) {
      if ( width >= breakpoints[x][0] && width <= breakpoints[x][1]) bp.push(x)
    }
    return bp.join(' ')
  }

  /*
   * Set to device height
   * Relies on element to have [data-set-device-height] attribute set
   * to one or many breakpoint names, e.g. `data-set-device-height="xs sm"`
   * for device's height to be applied at those breakpoints
   */
  function setDeviceHeights() {
    // Always get the site header height to remove from the element's height
    var siteHeaderHeight = $('.site-header').outerHeight()
    var deviceHeight = window.innerHeight - siteHeaderHeight

    // Set element to height of device
    $('[data-set-device-height]').each(function (i, elem) {
      var $elem = $(elem)
      var checkBp = $elem.attr('data-set-device-height').trim().toLowerCase()
      var setHeight = false

      // Turn elem setting into an array to iterate over later
      if (!/[, ]/.test(checkBp)) {
        checkBp = [checkBp]
      } else {
        checkBp = checkBp.split(/[, ]+/)
      }

      // Check if elem should be set to device's height
      for (var j in checkBp) {
        if (new RegExp(checkBp[j], 'i').test(currentBreakpoint)) {
          setHeight = checkBp[j]
          break
        }
      }

      // Set the height
      if (setHeight) {
        // @debug
        // console.log('Setting element height to device', currentBreakpoint, checkBp)
        $elem.css('height', deviceHeight + 'px').addClass('ui-set-device-height')
      } else {
        $elem.css('height', '').removeClass('ui-set-device-height')
      }
    })
  }

  /*
   * Equal Height
   * Sets multiple elements to be the equal (maximum) height
   * Elements require attribute [data-equal-height] set. You can also specify the
   * breakpoints you only want this to be applied to in this attribute, e.g.
   * `<div data-equal-height="xs">..</div>` would only be applied in `xs` breakpoint
   * If you want to separate equal height elements into groups, additionally
   * set the [data-equal-height-group] attribute to a unique string ID, e.g.
   * `<div data-equal-height="xs" data-equal-height-group="promo1">..</div>`
   */
  function setEqualHeights () {
    var equalHeights = {}
    $('[data-equal-height]').each(function (i, elem) {
      var $elem = $(elem)
      var groupName = $elem.attr('data-equal-height-group') || 'default'
      var elemHeight = $elem.css('height', '').outerHeight()

      // Create value to save max height to
      if (!equalHeights.hasOwnProperty(groupName)) equalHeights[groupName] = 0

      // Set max height
      if (elemHeight > equalHeights[groupName]) equalHeights[groupName] = elemHeight

    // After processing all, apply height (depending on breakpoint)
    }).each(function (i, elem) {
      var $elem = $(elem)
      var groupName = $elem.attr('data-equal-height-group') || 'default'
      var applyToBp = $elem.attr('data-equal-height')

      // Only apply to certain breakpoints
      if (applyToBp) {
        applyToBp = applyToBp.split(/[ ,]+/)

        // Test breakpoint
        if (new RegExp(applyToBp.join('|'), 'i').test(getActiveBreakpoints())) {
          $elem.height(equalHeights[groupName])

        // Remove height
        } else {
          $elem.css('height', '')
        }

      // No breakpoint set? Apply indiscriminately
      } else {
        $elem.height(equalHeights[groupName])
      }
    })
  }

  /*
   * Update Window
   */
  // Perform actions when the window needs to be updated
  function updateWindow() {
    clearTimeout(timerDebounceResize)

    // Get active breakpoints
    currentBreakpoint = getActiveBreakpoints()

    // Update the position of the project-single-menu top offset
    if (!$html.is('.ui-project-single-menu-fixed') && typeof projectSingleNavOffsetTop !== 'undefined') {
      projectSingleNavOffsetTop = $('.project-single-nav').first().offset().top - (parseInt($('.site-header').height(), 10) * 0.5)
    }

    // Update the position of the project-single-info offset
    offsetProjectSingleInfo()

    // Set device heights
    setDeviceHeights()

    // Update equal heights
    setEqualHeights()
  }

  // Scroll the window to a point, or an element on the page
  function scrollTo (point, cb, time) {
    // Get element to scroll too
    var $elem = $(point)
    var winScrollTop = $win.scrollTop()
    var toScrollTop = 0
    var diff

    // Try numeric value
    if ($elem.length === 0) {
      toScrollTop = parseInt(point, 10)
    } else {
      toScrollTop = $elem.eq(0).offset().top - 80 // Fixed header space
    }
    if (toScrollTop < 0) toScrollTop = 0

    if (toScrollTop !== winScrollTop) {
      diff = Math.max(toScrollTop, winScrollTop) - Math.min(toScrollTop, winScrollTop)

      // Calculate time to animate by the difference in distance
      if (typeof time === 'undefined') time = diff * 0.1
      if (time < 300) time = 300

      // @debug
      // console.log('scrollTo', {
      //   point: point,
      //   toScrollTop: toScrollTop,
      //   time: time
      // })

      $('html, body').animate({
        scrollTop: toScrollTop + 'px',
        skipGSAP: true
      }, time, 'swing', cb)
    }
  }

  // Scroll to an item which has been referenced on this page
  $doc.on(Utility.clickEvent, 'a[href^="#"]', function (event) {
    var elemId = $(this).attr('href').replace(/^[^#]*/, '')
    var $elem = $(elemId)
    var $self = $(this)

    // Ignore toggles
    if ($self.not('[data-toggle]').length > 0) {
      if ($elem.length > 0) {
        // Custom toggles
        // @note may need to refactor to place logic in better position
        if ($elem.is('.ui-toggle, [data-toggle-group]')) {
          // Get other elements
          $('[data-toggle-group="' + $elem.attr('data-toggle-group') + '"]').not($elem).hide()
          $elem.toggle()
          event.preventDefault()
          return
        }

        // event.preventDefault()
        scrollTo(elemId)
      }
    }
  })



  // Resize window (manual debouncing instead of using requestAnimationFrame)
  var timerDebounceResize = 0
  $win.on('resize', function () {
    clearTimeout(timerDebounceResize)
    timerDebounceResize = setTimeout(function () {
      updateWindow()
    }, 100)
  })

  /*
   * Sortables
   */
  // User interaction sort columns
  $doc.on(Utility.clickEvent, '[data-sortable-by]', function (event) {
    var $target = $(this)
    var columnName = $target.attr('data-sortable-by')

    event.preventDefault()
    $(this).parents('[data-sortable]').uiSortable('sort', columnName)
  })

  /*
   * Charts
   */
  // Convert chart placeholder JSON data to Chart Data
  var chartJSON = window.chartJSON || {}
  var chartData = {}
  for (var i in chartJSON) {
    chartData[i] = JSON.parse(chartJSON[i])
  }

  // Build charts
  function renderCharts() {
    $('[data-chart]:visible').not('[data-highcharts-chart]').each(function (i, elem) {
      // Get the data
      var $elem = $(elem)
      var chartDataKey = $elem.attr('data-chart')

      // Has data
      if (chartData.hasOwnProperty(chartDataKey)) {
        chartData[chartDataKey].credits = {
          enabled: false,
          text: ''
        }
        $elem.highcharts(chartData[chartDataKey])
      }
    })
  }

  // When viewing a tab, see if any charts need to be rendered inside
  $doc.on('shown.bs.tab', function (event) {
    renderCharts()

    // Scroll to the tab in the view too
    // @note currently disabling for fun
    // if ($(event.target).attr('href')) {
    //   scrollTo($(event.target).attr('href'))
    // }
  })

  /*
   * Project Single
   */
  $doc
    // -- Click to show map
    .on(Utility.clickEvent, '.ui-project-single-map-toggle', function (event) {
      event.preventDefault()
      toggleProjectSingleMap()
    })
    // -- Animation Events
    .on(Utility.transitionEndEvent, '.ui-project-single-map-opening', function (event) {
      showProjectSingleMap()
    })
    .on(Utility.transitionEndEvent, '.ui-project-single-map-closing', function (event) {
      hideProjectSingleMap()
    })

  function openProjectSingleMap () {
    // @debug console.log('openProjectSingleMap')
    if (isIE(9) || isIE('<9')) return showProjectSingleMap()
    if (!$html.is('.ui-project-single-map-open, .ui-project-single-map-opening')) {
      $html.removeClass('ui-project-single-map-open ui-project-single-map-closing').addClass('ui-project-single-map-opening')
    }
  }

  function closeProjectSingleMap () {
    // @debug console.log('closeProjectSingleMap')
    if (isIE(9) || isIE('<9')) return hideProjectSingleMap()
    $html.removeClass('ui-project-single-map-opening ui-project-single-map-open').addClass('ui-project-single-map-closing')
  }

  function showProjectSingleMap () {
    // @debug console.log('showProjectSingleMap')
    if (!$html.is('.ui-project-single-map-open')) {
      $html.removeClass('ui-project-single-map-opening ui-project-single-map-closing').addClass('ui-project-single-map-open')
      $('.ui-project-single-map-toggle .label').text(__.__('Hide map', 'projectSingleMapHideLabel'))
    }
  }

  function hideProjectSingleMap () {
    // @debug console.log('hideProjectSingleMap')
    $html.removeClass('ui-project-single-map-opening ui-project-single-map-open ui-project-single-map-closing')
    $('.ui-project-single-map-toggle .label').text(__.__('View map', 'projectSingleMapShowLabel'))
  }

  function toggleProjectSingleMap () {
    if($html.is('.ui-project-single-map-open, .ui-project-single-map-opening')) {
      closeProjectSingleMap()
    } else {
      openProjectSingleMap()
    }
  }

  // Project Single Info
  var $projectSingleInfoWrap = $('.project-single-info-wrap')
  var $projectSingleInfo = $('.project-single-info')
  if ($projectSingleInfoWrap.length > 0) {
    watchWindow.watch($projectSingleInfo, offsetProjectSingleInfo)
  }

  function offsetProjectSingleInfo () {
    // Only do if within the md/lg breakpoint
    if (/md|lg/.test(currentBreakpoint) && $projectSingleInfo.length > 0) {
      var winScrollTop = $win.scrollTop()
      var infoTop = ($projectSingleInfoWrap.offset().top + parseFloat($projectSingleInfo.css('margin-top')) - $siteHeader.height() - 25)
      var maxInfoTop = $siteFooter.offset().top - $win.innerHeight() - 25
      var translateAmount = winScrollTop - infoTop
      var offsetInfo = 0

      // Constrain info within certain area
      if (winScrollTop > infoTop) {
        if (winScrollTop < maxInfoTop) {
          offsetInfo = translateAmount
        } else {
          offsetInfo = maxInfoTop - 300
        }
      }

      // @debug
      // console.log({
      //   winScrollTop: winScrollTop,
      //   infoTop: infoTop,
      //   maxInfoTop: maxInfoTop,
      //   translateAmount: translateAmount,
      //   offsetInfo: offsetInfo
      // })

      $projectSingleInfo.css({
        transform: 'translateY(' + offsetInfo + 'px)'
      })

    // Reset
    } else {
      $projectSingleInfo.css('transform', '')
    }
  }

  /*
   * Collapse
   */
  // Mark on [data-toggle] triggers that the collapseable is/isn't collapsed
  $doc.on('shown.bs.collapse', function (event) {
    var targetTrigger = '[data-toggle="collapse"][data-target="#'+$(event.target).attr('id')+'"],[data-toggle="collapse"][href="#'+$(event.target).attr('id')+'"]'
    $(targetTrigger).addClass('ui-collapse-open')
  })
  .on('hidden.bs.collapse', function (event) {
    var targetTrigger = '[data-toggle="collapse"][data-target="#'+$(event.target).attr('id')+'"],[data-toggle="collapse"][href="#'+$(event.target).attr('id')+'"]'
    $(targetTrigger).removeClass('ui-collapse-open')
  })

  /*
   * Debug
   */
  if ($('#invalid-route').length > 0 && window.location.search) {
    var queryVars = []

    if (/^\?/.test(window.location.search)) {
      var qv = (window.location.search + '').replace('?', '')

      // Split again
      if (/&(amp;)?/i.test(qv)) {
        qv = qv.split(/&(amp;)?/)
      } else {
        qv = [qv]
      }

      // Process each qv
      for (var i = 0; i < qv.length; i++) {
        var qvSplit = qv[i].split('=')
        queryVars[qvSplit[0]] = qvSplit[1]
      }

      // Output the invalid route to the view
      $('#invalid-route').html('<pre><code>' + decodeURIComponent(queryVars.invalidroute) + '</code></pre>').css({
        display: 'block'
      })
    }
  }

  /*
   * Devenir Preteur
   */
  $doc.on('change', 'input#form-preter-address-is-correspondence', function (event) {
    checkAddressIsCorrespondence()
  })

  function checkAddressIsCorrespondence () {
    var address = ['street', 'code', 'ville', 'pays', 'telephone', 'mobile']
    if ($('input#form-preter-address-is-correspondence').is(':checked')) {
      $('#form-preter-fieldset-correspondence').hide()

      // Clear input values (checkbox defines addresses are same, so backend should reference only single address)
      // @note should only clear values on submit, in case user needs to edit again before submitting
      // for (var i = 0; i < address.length; i++) {
      //   $('[name="identity[correspondence][' + address[i] + ']"').val('')
      // }

    } else {
      $('#form-preter-fieldset-correspondence').show()
    }
  }
  checkAddressIsCorrespondence()

  /*
   * Validate IBAN Input
   */
  function checkIbanInput (event) {
    // Default: check all on the page
    if (typeof event === 'undefined') event = {target: '.custom-input-iban .iban-input', which: 0}

    $(event.target).each(function (i, elem) {
      // Get the current input
      var iban = $(this).val().toUpperCase().replace(/[^0-9A-Z]+/g, '')
      var caretPos = $(this).caret() || $(this).val().length

      // Reformat the input if entering text
      // @TODO when user types fast the caret sometimes gets left behind. May need to figure out better method for this
      if ((event.which >= 48 && event.which <= 90) || (event.which >= 96 && event.which <= 105) || event.which === 8 || event.which === 46 || event.which === 32) {
        if (iban) {
          // Format preview
          var previewIban = iban.match(/.{1,4}/g)
          var newCaretPos = (caretPos % 5 === 0 ? caretPos + 1 : caretPos)

          // @debug
          // console.log({
          //   value: $(this).val(),
          //   valueLength: $(this).val().length,
          //   iban: iban,
          //   ibanLength: iban.length,
          //   groupCount: previewIban.length,
          //   groupCountDivided: previewIban.length / 4,
          //   groupCountMod: previewIban.length % 4,
          //   caretPos: caretPos,
          //   caretPosDivided: caretPos / 4,
          //   caretPosMod: caretPos % 4
          // })

          // Add in spaces and assign the new caret position
          $(this).val(previewIban.join(' ')).caret(newCaretPos)
        }
      }

      // Check if valid
      if (Iban.isValid(iban)) {
        // Valid
      } else {
        // Invalid
      }
    })
  }
  $doc.on('keyup', '.custom-input-iban .iban-input', checkIbanInput)
  checkIbanInput()

  /*
   * Packery
   */
  $('[data-packery]').each(function (i, elem) {
    var $elem = $(elem)
    var elemOptions = JSON.parse($elem.attr('data-packery') || '{}')

    // @debug
    // console.log('data-packery options', elem, elemOptions)

    $elem.packery(elemOptions)

    // Draggable items
    if ($elem.find('.draggable, [data-draggable]').length > 0) {
      $elem.find('.draggable, [data-draggable]').each(function (j, item) {
        var itemOptions = JSON.parse($(item).attr('data-draggable') || '{}')

        // Special case
        if ($(item).is('.dashboard-panel')) {
          itemOptions.handle = '.dashboard-panel-title'
          itemOptions.containment = true
        }

        var draggie = new Draggabilly(item, itemOptions)
        $elem.packery('bindDraggabillyEvents', draggie)
      })
    }
  })

  // Perform on initialisation
  svg4everybody()
  renderCharts()
  updateWindow()
})

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})

},{"AutoComplete":4,"DashboardPanel":5,"ElementBounds":14,"FileAttach":6,"FormValidation":7,"PasswordCheck":8,"Sortable":9,"TextCount":10,"TimeCount":11,"Tween":16,"Utility":17,"WatchScroll":18,"__":19,"iban":1,"svg4everybody":3}],21:[function(require,module,exports){
module.exports={
  "en": {
    "noResults": "No results found. Try another search"
  },
  "fr": {
    "noResults": "Results n'existe pas. Essayer un cherche encore"
  }
}
},{}],22:[function(require,module,exports){
module.exports={
  "fr": {
    "numberDecimal":  ",",
    "numberMilli":    " ",
    "numberCurrency": "€",

    "timeCountDays": "jours",
    "timeCountRemaining": "restantes",

    "siteMobileMenuOpenLabel": "Ouvrir le menu",
    "siteMobileMenuCloseLabel": "Fermer le menu",

    "siteSearchLabel": "Recherche Unilend",
    "siteSearchInputPlaceholder": "Une question ?",
    "siteSearchSubmitLabel": "Lancer la recherche",
    "siteSearchShowAllResultsLabel": "Voir tous les résultats",

    "siteUserRegisterLabel": "Inscription",
    "siteUserLoginLabel": "Connexion",

    "siteFooterCopyrightAllRights": "Tous droits réservés",

    "socialFollowOnFacebookLabel": "Suivez Unilend sur Facebook",
    "socialFollowOnTwitterLabel": "Suivez Unilend sur Twitter",

    "projectPeriodExpired": "Projet terminée",

    "projectListViewListLabel": "Voir la liste",
    "projectListViewMapLabel": "Voir sur la carte",
    "projectListItemTypeSingle": "Projet",
    "projectListItemTypePlural": "Projets",

    "projectListFilterCategory": "Catégorie",
    "projectListFilterCost": "Cost",
    "projectListFilterInterest": "Taux Intérêt Moyen",
    "projectListFilterRating": "Note",
    "projectListFilterPeriod": "Temps restant",

    "projectListItemOffersLabelSingle": "offre",
    "projectListItemOffersLabelPlural": "offres",
    "projectListItemOffersUserStatusInProgress": "en cours",
    "projectListItemOffersUserStatusAccepted": "accepté",
    "projectListItemOffersUserStatusRejected": "rejeté",
    "projectListItemRatingLabel": "Rated %d sur 5",
    "projectListItemPeriodLabel": "%d jours",
    "projectListItemPeriodExpired": "Projet terminée",
    "projectListItemViewLabel": "Voir les informations sur ce projet",

    "projectSinglePeriodExpired": "Projet terminée",
    "projectSingleRatingLabel": "Rated %d sur 5",
    "projectSingleMapShowLabel": "Voir la carte",
    "projectSingleMapHideLabel": "Cacher la carte",

    "paginationItemLabelSingle": "Page",
    "paginationItemLabelPlural": "Pages",
    "paginationInfoLocationLabel": "%d sur %d",
    "paginationInfoNextLabel": "Voir %d %s suivants",
    "paginationIndexPreviousLabel": "Les %d précédents %s",
    "paginationIndexNextLabel": "%d %s suivants",

    "listSharingShareTextDefault": "Regardez %shareTitle% sur Unilend",
    "listSharingLinkFacebookShareLabel": "Partager sur Facebook",
    "listSharingLinkTwitterShareLabel": "Partager sur Twitter",

    "pageHomePreterProjectListTitle": "%d projets en cours",

    "pageProjetsIndexHeaderTitle": "%s projets en cours, %s projets financés, %s prêteurs actifs."
  },
  "en": {
    "numberDecimal":  ".",
    "numberMilli":    ",",
    "numberCurrency": "$",

    "timeCountDays": "days",
    "timeCountRemaining": "remaining",

    "siteMobileMenuOpenLabel": "Open Menu",
    "siteMobileMenuCloseLabel": "Close Menu",

    "siteSearchLabel": "Search Unilend",
    "siteSearchInputPlaceholder": "Got a question?",
    "siteSearchSubmitLabel": "Submit Search",
    "siteSearchShowAllResultsLabel": "View all search results",

    "siteUserRegisterLabel": "Register",
    "siteUserLoginLabel": "Sign in",

    "siteFooterCopyrightAllRights": "All Rights Reserved",

    "socialFollowOnFacebookLabel": "Follow Unilend on Facebook",
    "socialFollowOnTwitterLabel": "Follow Unilend on Twitter",

    "projectPeriodExpired": "Project expired",

    "projectListViewListLabel": "List View",
    "projectListViewMapLabel": "Map View",
    "projectListItemTypeSingle": "Project",
    "projectListItemTypePlural": "Projects",

    "projectListFilterCategory": "Category",
    "projectListFilterCost": "Cost",
    "projectListFilterInterest": "Interest",
    "projectListFilterRating": "Rating",
    "projectListFilterPeriod": "Time Remaining",

    "projectListItemOffersLabelSingle": "offer",
    "projectListItemOffersLabelPlural": "offers",
    "projectListItemOffersUserStatusInProgress": "en cours",
    "projectListItemOffersUserStatusAccepted": "accepté",
    "projectListItemOffersUserStatusRejected": "rejeté",
    "projectListItemRatingLabel": "Rated %d out of 5",
    "projectListItemPeriodLabel": "%d days left",
    "projectListItemPeriodExpired": "Project expired",
    "projectListItemViewLabel": "View more information about this project",

    "projectSinglePeriodExpired": "Project expired",
    "projectSingleRatingLabel": "Rated %d out of 5",
    "projectSingleMapShowLabel": "View map",
    "projectSingleMapHideLabel": "Hide map",

    "paginationItemLabelSingle": "Page",
    "paginationItemLabelPlural": "Pages",
    "paginationInfoLocationLabel": "%d of %d",
    "paginationInfoNextLabel": "View next %d %s",
    "paginationIndexPreviousLabel": "Previous %d %s",
    "paginationIndexNextLabel": "Next %d %s",

    "listSharingShareTextDefault": "Check out %shareTitle% on Unilend",
    "listSharingLinkFacebookShareLabel": "Share to Facebook",
    "listSharingLinkTwitterShareLabel": "Share to Twitter",

    "pageHomePreterProjectListTitle": "%d Active Projects",

    "pageProjetsIndexHeaderTitle": "%s projects in progress, %s projects financed, %s active lenders."
  },
  "en-gb": {
    "numberDecimal":  ".",
    "numberMilli":    ",",
    "numberCurrency": "£"
  },
  "es": {
    "numberDecimal":  ",",
    "numberMilli":    ".",
    "numberCurrency": "€"
  }
}
},{}]},{},[20])
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbIm5vZGVfbW9kdWxlcy9icm93c2VyaWZ5L25vZGVfbW9kdWxlcy9icm93c2VyLXBhY2svX3ByZWx1ZGUuanMiLCJub2RlX21vZHVsZXMvaWJhbi9pYmFuLmpzIiwibm9kZV9tb2R1bGVzL3NwcmludGYtanMvc3JjL3NwcmludGYuanMiLCJub2RlX21vZHVsZXMvc3ZnNGV2ZXJ5Ym9keS9kaXN0L3N2ZzRldmVyeWJvZHkuanMiLCJzcmMvanMvYXBwL2NvbXBvbmVudHMvQXV0b0NvbXBsZXRlLmpzIiwic3JjL2pzL2FwcC9jb21wb25lbnRzL0Rhc2hib2FyZFBhbmVsLmpzIiwic3JjL2pzL2FwcC9jb21wb25lbnRzL0ZpbGVBdHRhY2guanMiLCJzcmMvanMvYXBwL2NvbXBvbmVudHMvRm9ybVZhbGlkYXRpb24uanMiLCJzcmMvanMvYXBwL2NvbXBvbmVudHMvUGFzc3dvcmRDaGVjay5qcyIsInNyYy9qcy9hcHAvY29tcG9uZW50cy9Tb3J0YWJsZS5qcyIsInNyYy9qcy9hcHAvY29tcG9uZW50cy9UZXh0Q291bnQuanMiLCJzcmMvanMvYXBwL2NvbXBvbmVudHMvVGltZUNvdW50LmpzIiwic3JjL2pzL2FwcC9saWIvRGljdGlvbmFyeS5qcyIsInNyYy9qcy9hcHAvbGliL0VsZW1lbnRBdHRyc09iamVjdC5qcyIsInNyYy9qcy9hcHAvbGliL0VsZW1lbnRCb3VuZHMuanMiLCJzcmMvanMvYXBwL2xpYi9UZW1wbGF0aW5nLmpzIiwic3JjL2pzL2FwcC9saWIvVHdlZW4uanMiLCJzcmMvanMvYXBwL2xpYi9VdGlsaXR5LmpzIiwic3JjL2pzL2FwcC9saWIvV2F0Y2hTY3JvbGwuanMiLCJzcmMvanMvYXBwL2xpYi9fXy5qcyIsInNyYy9qcy9tYWluLmRldi5qcyIsInNyYy9sYW5nL0F1dG9Db21wbGV0ZS5sYW5nLmpzb24iLCJzcmMvbGFuZy9VbmlsZW5kLmxhbmcuanNvbiJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQ0FBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDL1pBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FDaE5BO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7O0FDNUZBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7QUMzU0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7OztBQ2pIQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7O0FDalRBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7O0FDOTFCQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7OztBQzdTQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7O0FDM1ZBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7OztBQzdPQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7QUM5R0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7O0FDOUlBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7QUNsREE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7QUN6YkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUM5Q0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7OztBQ2pJQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7OztBQ3pIQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7Ozs7QUN2Z0JBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7Ozs7O0FDVkE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7O0FDOXVDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQ1BBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EiLCJmaWxlIjoiZ2VuZXJhdGVkLmpzIiwic291cmNlUm9vdCI6IiIsInNvdXJjZXNDb250ZW50IjpbIihmdW5jdGlvbiBlKHQsbixyKXtmdW5jdGlvbiBzKG8sdSl7aWYoIW5bb10pe2lmKCF0W29dKXt2YXIgYT10eXBlb2YgcmVxdWlyZT09XCJmdW5jdGlvblwiJiZyZXF1aXJlO2lmKCF1JiZhKXJldHVybiBhKG8sITApO2lmKGkpcmV0dXJuIGkobywhMCk7dmFyIGY9bmV3IEVycm9yKFwiQ2Fubm90IGZpbmQgbW9kdWxlICdcIitvK1wiJ1wiKTt0aHJvdyBmLmNvZGU9XCJNT0RVTEVfTk9UX0ZPVU5EXCIsZn12YXIgbD1uW29dPXtleHBvcnRzOnt9fTt0W29dWzBdLmNhbGwobC5leHBvcnRzLGZ1bmN0aW9uKGUpe3ZhciBuPXRbb11bMV1bZV07cmV0dXJuIHMobj9uOmUpfSxsLGwuZXhwb3J0cyxlLHQsbixyKX1yZXR1cm4gbltvXS5leHBvcnRzfXZhciBpPXR5cGVvZiByZXF1aXJlPT1cImZ1bmN0aW9uXCImJnJlcXVpcmU7Zm9yKHZhciBvPTA7bzxyLmxlbmd0aDtvKyspcyhyW29dKTtyZXR1cm4gc30pIiwiKGZ1bmN0aW9uIChyb290LCBmYWN0b3J5KSB7XG4gICAgaWYgKHR5cGVvZiBkZWZpbmUgPT09ICdmdW5jdGlvbicgJiYgZGVmaW5lLmFtZCkge1xuICAgICAgICAvLyBBTUQuIFJlZ2lzdGVyIGFzIGFuIGFub255bW91cyBtb2R1bGUuXG4gICAgICAgIGRlZmluZShbJ2V4cG9ydHMnXSwgZmFjdG9yeSk7XG4gICAgfSBlbHNlIGlmICh0eXBlb2YgZXhwb3J0cyA9PT0gJ29iamVjdCcgJiYgdHlwZW9mIGV4cG9ydHMubm9kZU5hbWUgIT09ICdzdHJpbmcnKSB7XG4gICAgICAgIC8vIENvbW1vbkpTXG4gICAgICAgIGZhY3RvcnkoZXhwb3J0cyk7XG4gICAgfSBlbHNlIHtcbiAgICAgICAgLy8gQnJvd3NlciBnbG9iYWxzXG4gICAgICAgIGZhY3Rvcnkocm9vdC5JQkFOID0ge30pO1xuICAgIH1cbn0odGhpcywgZnVuY3Rpb24oZXhwb3J0cyl7XG5cbiAgICAvLyBBcnJheS5wcm90b3R5cGUubWFwIHBvbHlmaWxsXG4gICAgLy8gY29kZSBmcm9tIGh0dHBzOi8vZGV2ZWxvcGVyLm1vemlsbGEub3JnL2VuLVVTL2RvY3MvSmF2YVNjcmlwdC9SZWZlcmVuY2UvR2xvYmFsX09iamVjdHMvQXJyYXkvbWFwXG4gICAgaWYgKCFBcnJheS5wcm90b3R5cGUubWFwKXtcbiAgICAgICAgQXJyYXkucHJvdG90eXBlLm1hcCA9IGZ1bmN0aW9uKGZ1biAvKiwgdGhpc0FyZyAqLyl7XG4gICAgICAgICAgICBcInVzZSBzdHJpY3RcIjtcblxuICAgICAgICAgICAgaWYgKHRoaXMgPT09IHZvaWQgMCB8fCB0aGlzID09PSBudWxsKVxuICAgICAgICAgICAgICAgIHRocm93IG5ldyBUeXBlRXJyb3IoKTtcblxuICAgICAgICAgICAgdmFyIHQgPSBPYmplY3QodGhpcyk7XG4gICAgICAgICAgICB2YXIgbGVuID0gdC5sZW5ndGggPj4+IDA7XG4gICAgICAgICAgICBpZiAodHlwZW9mIGZ1biAhPT0gXCJmdW5jdGlvblwiKVxuICAgICAgICAgICAgICAgIHRocm93IG5ldyBUeXBlRXJyb3IoKTtcblxuICAgICAgICAgICAgdmFyIHJlcyA9IG5ldyBBcnJheShsZW4pO1xuICAgICAgICAgICAgdmFyIHRoaXNBcmcgPSBhcmd1bWVudHMubGVuZ3RoID49IDIgPyBhcmd1bWVudHNbMV0gOiB2b2lkIDA7XG4gICAgICAgICAgICBmb3IgKHZhciBpID0gMDsgaSA8IGxlbjsgaSsrKVxuICAgICAgICAgICAge1xuICAgICAgICAgICAgICAgIC8vIE5PVEU6IEFic29sdXRlIGNvcnJlY3RuZXNzIHdvdWxkIGRlbWFuZCBPYmplY3QuZGVmaW5lUHJvcGVydHlcbiAgICAgICAgICAgICAgICAvLyAgICAgICBiZSB1c2VkLiAgQnV0IHRoaXMgbWV0aG9kIGlzIGZhaXJseSBuZXcsIGFuZCBmYWlsdXJlIGlzXG4gICAgICAgICAgICAgICAgLy8gICAgICAgcG9zc2libGUgb25seSBpZiBPYmplY3QucHJvdG90eXBlIG9yIEFycmF5LnByb3RvdHlwZVxuICAgICAgICAgICAgICAgIC8vICAgICAgIGhhcyBhIHByb3BlcnR5IHxpfCAodmVyeSB1bmxpa2VseSksIHNvIHVzZSBhIGxlc3MtY29ycmVjdFxuICAgICAgICAgICAgICAgIC8vICAgICAgIGJ1dCBtb3JlIHBvcnRhYmxlIGFsdGVybmF0aXZlLlxuICAgICAgICAgICAgICAgIGlmIChpIGluIHQpXG4gICAgICAgICAgICAgICAgICAgIHJlc1tpXSA9IGZ1bi5jYWxsKHRoaXNBcmcsIHRbaV0sIGksIHQpO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICByZXR1cm4gcmVzO1xuICAgICAgICB9O1xuICAgIH1cblxuICAgIHZhciBBID0gJ0EnLmNoYXJDb2RlQXQoMCksXG4gICAgICAgIFogPSAnWicuY2hhckNvZGVBdCgwKTtcblxuICAgIC8qKlxuICAgICAqIFByZXBhcmUgYW4gSUJBTiBmb3IgbW9kIDk3IGNvbXB1dGF0aW9uIGJ5IG1vdmluZyB0aGUgZmlyc3QgNCBjaGFycyB0byB0aGUgZW5kIGFuZCB0cmFuc2Zvcm1pbmcgdGhlIGxldHRlcnMgdG9cbiAgICAgKiBudW1iZXJzIChBID0gMTAsIEIgPSAxMSwgLi4uLCBaID0gMzUpLCBhcyBzcGVjaWZpZWQgaW4gSVNPMTM2MTYuXG4gICAgICpcbiAgICAgKiBAcGFyYW0ge3N0cmluZ30gaWJhbiB0aGUgSUJBTlxuICAgICAqIEByZXR1cm5zIHtzdHJpbmd9IHRoZSBwcmVwYXJlZCBJQkFOXG4gICAgICovXG4gICAgZnVuY3Rpb24gaXNvMTM2MTZQcmVwYXJlKGliYW4pIHtcbiAgICAgICAgaWJhbiA9IGliYW4udG9VcHBlckNhc2UoKTtcbiAgICAgICAgaWJhbiA9IGliYW4uc3Vic3RyKDQpICsgaWJhbi5zdWJzdHIoMCw0KTtcblxuICAgICAgICByZXR1cm4gaWJhbi5zcGxpdCgnJykubWFwKGZ1bmN0aW9uKG4pe1xuICAgICAgICAgICAgdmFyIGNvZGUgPSBuLmNoYXJDb2RlQXQoMCk7XG4gICAgICAgICAgICBpZiAoY29kZSA+PSBBICYmIGNvZGUgPD0gWil7XG4gICAgICAgICAgICAgICAgLy8gQSA9IDEwLCBCID0gMTEsIC4uLiBaID0gMzVcbiAgICAgICAgICAgICAgICByZXR1cm4gY29kZSAtIEEgKyAxMDtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIG47XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pLmpvaW4oJycpO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIENhbGN1bGF0ZXMgdGhlIE1PRCA5NyAxMCBvZiB0aGUgcGFzc2VkIElCQU4gYXMgc3BlY2lmaWVkIGluIElTTzcwNjQuXG4gICAgICpcbiAgICAgKiBAcGFyYW0gaWJhblxuICAgICAqIEByZXR1cm5zIHtudW1iZXJ9XG4gICAgICovXG4gICAgZnVuY3Rpb24gaXNvNzA2NE1vZDk3XzEwKGliYW4pIHtcbiAgICAgICAgdmFyIHJlbWFpbmRlciA9IGliYW4sXG4gICAgICAgICAgICBibG9jaztcblxuICAgICAgICB3aGlsZSAocmVtYWluZGVyLmxlbmd0aCA+IDIpe1xuICAgICAgICAgICAgYmxvY2sgPSByZW1haW5kZXIuc2xpY2UoMCwgOSk7XG4gICAgICAgICAgICByZW1haW5kZXIgPSBwYXJzZUludChibG9jaywgMTApICUgOTcgKyByZW1haW5kZXIuc2xpY2UoYmxvY2subGVuZ3RoKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHJldHVybiBwYXJzZUludChyZW1haW5kZXIsIDEwKSAlIDk3O1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIFBhcnNlIHRoZSBCQkFOIHN0cnVjdHVyZSB1c2VkIHRvIGNvbmZpZ3VyZSBlYWNoIElCQU4gU3BlY2lmaWNhdGlvbiBhbmQgcmV0dXJucyBhIG1hdGNoaW5nIHJlZ3VsYXIgZXhwcmVzc2lvbi5cbiAgICAgKiBBIHN0cnVjdHVyZSBpcyBjb21wb3NlZCBvZiBibG9ja3Mgb2YgMyBjaGFyYWN0ZXJzIChvbmUgbGV0dGVyIGFuZCAyIGRpZ2l0cykuIEVhY2ggYmxvY2sgcmVwcmVzZW50c1xuICAgICAqIGEgbG9naWNhbCBncm91cCBpbiB0aGUgdHlwaWNhbCByZXByZXNlbnRhdGlvbiBvZiB0aGUgQkJBTi4gRm9yIGVhY2ggZ3JvdXAsIHRoZSBsZXR0ZXIgaW5kaWNhdGVzIHdoaWNoIGNoYXJhY3RlcnNcbiAgICAgKiBhcmUgYWxsb3dlZCBpbiB0aGlzIGdyb3VwIGFuZCB0aGUgZm9sbG93aW5nIDItZGlnaXRzIG51bWJlciB0ZWxscyB0aGUgbGVuZ3RoIG9mIHRoZSBncm91cC5cbiAgICAgKlxuICAgICAqIEBwYXJhbSB7c3RyaW5nfSBzdHJ1Y3R1cmUgdGhlIHN0cnVjdHVyZSB0byBwYXJzZVxuICAgICAqIEByZXR1cm5zIHtSZWdFeHB9XG4gICAgICovXG4gICAgZnVuY3Rpb24gcGFyc2VTdHJ1Y3R1cmUoc3RydWN0dXJlKXtcbiAgICAgICAgLy8gc3BsaXQgaW4gYmxvY2tzIG9mIDMgY2hhcnNcbiAgICAgICAgdmFyIHJlZ2V4ID0gc3RydWN0dXJlLm1hdGNoKC8oLnszfSkvZykubWFwKGZ1bmN0aW9uKGJsb2NrKXtcblxuICAgICAgICAgICAgLy8gcGFyc2UgZWFjaCBzdHJ1Y3R1cmUgYmxvY2sgKDEtY2hhciArIDItZGlnaXRzKVxuICAgICAgICAgICAgdmFyIGZvcm1hdCxcbiAgICAgICAgICAgICAgICBwYXR0ZXJuID0gYmxvY2suc2xpY2UoMCwgMSksXG4gICAgICAgICAgICAgICAgcmVwZWF0cyA9IHBhcnNlSW50KGJsb2NrLnNsaWNlKDEpLCAxMCk7XG5cbiAgICAgICAgICAgIHN3aXRjaCAocGF0dGVybil7XG4gICAgICAgICAgICAgICAgY2FzZSBcIkFcIjogZm9ybWF0ID0gXCIwLTlBLVphLXpcIjsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSBcIkJcIjogZm9ybWF0ID0gXCIwLTlBLVpcIjsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSBcIkNcIjogZm9ybWF0ID0gXCJBLVphLXpcIjsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSBcIkZcIjogZm9ybWF0ID0gXCIwLTlcIjsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSBcIkxcIjogZm9ybWF0ID0gXCJhLXpcIjsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSBcIlVcIjogZm9ybWF0ID0gXCJBLVpcIjsgYnJlYWs7XG4gICAgICAgICAgICAgICAgY2FzZSBcIldcIjogZm9ybWF0ID0gXCIwLTlhLXpcIjsgYnJlYWs7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHJldHVybiAnKFsnICsgZm9ybWF0ICsgJ117JyArIHJlcGVhdHMgKyAnfSknO1xuICAgICAgICB9KTtcblxuICAgICAgICByZXR1cm4gbmV3IFJlZ0V4cCgnXicgKyByZWdleC5qb2luKCcnKSArICckJyk7XG4gICAgfVxuXG4gICAgLyoqXG4gICAgICogQ3JlYXRlIGEgbmV3IFNwZWNpZmljYXRpb24gZm9yIGEgdmFsaWQgSUJBTiBudW1iZXIuXG4gICAgICpcbiAgICAgKiBAcGFyYW0gY291bnRyeUNvZGUgdGhlIGNvZGUgb2YgdGhlIGNvdW50cnlcbiAgICAgKiBAcGFyYW0gbGVuZ3RoIHRoZSBsZW5ndGggb2YgdGhlIElCQU5cbiAgICAgKiBAcGFyYW0gc3RydWN0dXJlIHRoZSBzdHJ1Y3R1cmUgb2YgdGhlIHVuZGVybHlpbmcgQkJBTiAoZm9yIHZhbGlkYXRpb24gYW5kIGZvcm1hdHRpbmcpXG4gICAgICogQHBhcmFtIGV4YW1wbGUgYW4gZXhhbXBsZSB2YWxpZCBJQkFOXG4gICAgICogQGNvbnN0cnVjdG9yXG4gICAgICovXG4gICAgZnVuY3Rpb24gU3BlY2lmaWNhdGlvbihjb3VudHJ5Q29kZSwgbGVuZ3RoLCBzdHJ1Y3R1cmUsIGV4YW1wbGUpe1xuXG4gICAgICAgIHRoaXMuY291bnRyeUNvZGUgPSBjb3VudHJ5Q29kZTtcbiAgICAgICAgdGhpcy5sZW5ndGggPSBsZW5ndGg7XG4gICAgICAgIHRoaXMuc3RydWN0dXJlID0gc3RydWN0dXJlO1xuICAgICAgICB0aGlzLmV4YW1wbGUgPSBleGFtcGxlO1xuICAgIH1cblxuICAgIC8qKlxuICAgICAqIExhenktbG9hZGVkIHJlZ2V4IChwYXJzZSB0aGUgc3RydWN0dXJlIGFuZCBjb25zdHJ1Y3QgdGhlIHJlZ3VsYXIgZXhwcmVzc2lvbiB0aGUgZmlyc3QgdGltZSB3ZSBuZWVkIGl0IGZvciB2YWxpZGF0aW9uKVxuICAgICAqL1xuICAgIFNwZWNpZmljYXRpb24ucHJvdG90eXBlLl9yZWdleCA9IGZ1bmN0aW9uKCl7XG4gICAgICAgIHJldHVybiB0aGlzLl9jYWNoZWRSZWdleCB8fCAodGhpcy5fY2FjaGVkUmVnZXggPSBwYXJzZVN0cnVjdHVyZSh0aGlzLnN0cnVjdHVyZSkpXG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIENoZWNrIGlmIHRoZSBwYXNzZWQgaWJhbiBpcyB2YWxpZCBhY2NvcmRpbmcgdG8gdGhpcyBzcGVjaWZpY2F0aW9uLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IGliYW4gdGhlIGliYW4gdG8gdmFsaWRhdGVcbiAgICAgKiBAcmV0dXJucyB7Ym9vbGVhbn0gdHJ1ZSBpZiB2YWxpZCwgZmFsc2Ugb3RoZXJ3aXNlXG4gICAgICovXG4gICAgU3BlY2lmaWNhdGlvbi5wcm90b3R5cGUuaXNWYWxpZCA9IGZ1bmN0aW9uKGliYW4pe1xuICAgICAgICByZXR1cm4gdGhpcy5sZW5ndGggPT0gaWJhbi5sZW5ndGhcbiAgICAgICAgICAgICYmIHRoaXMuY291bnRyeUNvZGUgPT09IGliYW4uc2xpY2UoMCwyKVxuICAgICAgICAgICAgJiYgdGhpcy5fcmVnZXgoKS50ZXN0KGliYW4uc2xpY2UoNCkpXG4gICAgICAgICAgICAmJiBpc283MDY0TW9kOTdfMTAoaXNvMTM2MTZQcmVwYXJlKGliYW4pKSA9PSAxO1xuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBDb252ZXJ0IHRoZSBwYXNzZWQgSUJBTiB0byBhIGNvdW50cnktc3BlY2lmaWMgQkJBTi5cbiAgICAgKlxuICAgICAqIEBwYXJhbSBpYmFuIHRoZSBJQkFOIHRvIGNvbnZlcnRcbiAgICAgKiBAcGFyYW0gc2VwYXJhdG9yIHRoZSBzZXBhcmF0b3IgdG8gdXNlIGJldHdlZW4gQkJBTiBibG9ja3NcbiAgICAgKiBAcmV0dXJucyB7c3RyaW5nfSB0aGUgQkJBTlxuICAgICAqL1xuICAgIFNwZWNpZmljYXRpb24ucHJvdG90eXBlLnRvQkJBTiA9IGZ1bmN0aW9uKGliYW4sIHNlcGFyYXRvcikge1xuICAgICAgICByZXR1cm4gdGhpcy5fcmVnZXgoKS5leGVjKGliYW4uc2xpY2UoNCkpLnNsaWNlKDEpLmpvaW4oc2VwYXJhdG9yKTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogQ29udmVydCB0aGUgcGFzc2VkIEJCQU4gdG8gYW4gSUJBTiBmb3IgdGhpcyBjb3VudHJ5IHNwZWNpZmljYXRpb24uXG4gICAgICogUGxlYXNlIG5vdGUgdGhhdCA8aT5cImdlbmVyYXRpb24gb2YgdGhlIElCQU4gc2hhbGwgYmUgdGhlIGV4Y2x1c2l2ZSByZXNwb25zaWJpbGl0eSBvZiB0aGUgYmFuay9icmFuY2ggc2VydmljaW5nIHRoZSBhY2NvdW50XCI8L2k+LlxuICAgICAqIFRoaXMgbWV0aG9kIGltcGxlbWVudHMgdGhlIHByZWZlcnJlZCBhbGdvcml0aG0gZGVzY3JpYmVkIGluIGh0dHA6Ly9lbi53aWtpcGVkaWEub3JnL3dpa2kvSW50ZXJuYXRpb25hbF9CYW5rX0FjY291bnRfTnVtYmVyI0dlbmVyYXRpbmdfSUJBTl9jaGVja19kaWdpdHNcbiAgICAgKlxuICAgICAqIEBwYXJhbSBiYmFuIHRoZSBCQkFOIHRvIGNvbnZlcnQgdG8gSUJBTlxuICAgICAqIEByZXR1cm5zIHtzdHJpbmd9IHRoZSBJQkFOXG4gICAgICovXG4gICAgU3BlY2lmaWNhdGlvbi5wcm90b3R5cGUuZnJvbUJCQU4gPSBmdW5jdGlvbihiYmFuKSB7XG4gICAgICAgIGlmICghdGhpcy5pc1ZhbGlkQkJBTihiYmFuKSl7XG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ0ludmFsaWQgQkJBTicpO1xuICAgICAgICB9XG5cbiAgICAgICAgdmFyIHJlbWFpbmRlciA9IGlzbzcwNjRNb2Q5N18xMChpc28xMzYxNlByZXBhcmUodGhpcy5jb3VudHJ5Q29kZSArICcwMCcgKyBiYmFuKSksXG4gICAgICAgICAgICBjaGVja0RpZ2l0ID0gKCcwJyArICg5OCAtIHJlbWFpbmRlcikpLnNsaWNlKC0yKTtcblxuICAgICAgICByZXR1cm4gdGhpcy5jb3VudHJ5Q29kZSArIGNoZWNrRGlnaXQgKyBiYmFuO1xuICAgIH07XG5cbiAgICAvKipcbiAgICAgKiBDaGVjayBvZiB0aGUgcGFzc2VkIEJCQU4gaXMgdmFsaWQuXG4gICAgICogVGhpcyBmdW5jdGlvbiBvbmx5IGNoZWNrcyB0aGUgZm9ybWF0IG9mIHRoZSBCQkFOIChsZW5ndGggYW5kIG1hdGNoaW5nIHRoZSBsZXRldHIvbnVtYmVyIHNwZWNzKSBidXQgZG9lcyBub3RcbiAgICAgKiB2ZXJpZnkgdGhlIGNoZWNrIGRpZ2l0LlxuICAgICAqXG4gICAgICogQHBhcmFtIGJiYW4gdGhlIEJCQU4gdG8gdmFsaWRhdGVcbiAgICAgKiBAcmV0dXJucyB7Ym9vbGVhbn0gdHJ1ZSBpZiB0aGUgcGFzc2VkIGJiYW4gaXMgYSB2YWxpZCBCQkFOIGFjY29yZGluZyB0byB0aGlzIHNwZWNpZmljYXRpb24sIGZhbHNlIG90aGVyd2lzZVxuICAgICAqL1xuICAgIFNwZWNpZmljYXRpb24ucHJvdG90eXBlLmlzVmFsaWRCQkFOID0gZnVuY3Rpb24oYmJhbikge1xuICAgICAgICByZXR1cm4gdGhpcy5sZW5ndGggLSA0ID09IGJiYW4ubGVuZ3RoXG4gICAgICAgICAgICAmJiB0aGlzLl9yZWdleCgpLnRlc3QoYmJhbik7XG4gICAgfTtcblxuICAgIHZhciBjb3VudHJpZXMgPSB7fTtcblxuICAgIGZ1bmN0aW9uIGFkZFNwZWNpZmljYXRpb24oSUJBTil7XG4gICAgICAgIGNvdW50cmllc1tJQkFOLmNvdW50cnlDb2RlXSA9IElCQU47XG4gICAgfVxuXG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkFEXCIsIDI0LCBcIkYwNEYwNEExMlwiLCAgICAgICAgICBcIkFEMTIwMDAxMjAzMDIwMDM1OTEwMDEwMFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkFFXCIsIDIzLCBcIkYwM0YxNlwiLCAgICAgICAgICAgICBcIkFFMDcwMzMxMjM0NTY3ODkwMTIzNDU2XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQUxcIiwgMjgsIFwiRjA4QTE2XCIsICAgICAgICAgICAgIFwiQUw0NzIxMjExMDA5MDAwMDAwMDIzNTY5ODc0MVwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkFUXCIsIDIwLCBcIkYwNUYxMVwiLCAgICAgICAgICAgICBcIkFUNjExOTA0MzAwMjM0NTczMjAxXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQVpcIiwgMjgsIFwiVTA0QTIwXCIsICAgICAgICAgICAgIFwiQVoyMU5BQlowMDAwMDAwMDEzNzAxMDAwMTk0NFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkJBXCIsIDIwLCBcIkYwM0YwM0YwOEYwMlwiLCAgICAgICBcIkJBMzkxMjkwMDc5NDAxMDI4NDk0XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQkVcIiwgMTYsIFwiRjAzRjA3RjAyXCIsICAgICAgICAgIFwiQkU2ODUzOTAwNzU0NzAzNFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkJHXCIsIDIyLCBcIlUwNEYwNEYwMkEwOFwiLCAgICAgICBcIkJHODBCTkJHOTY2MTEwMjAzNDU2NzhcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJCSFwiLCAyMiwgXCJVMDRBMTRcIiwgICAgICAgICAgICAgXCJCSDY3Qk1BRzAwMDAxMjk5MTIzNDU2XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQlJcIiwgMjksIFwiRjA4RjA1RjEwVTAxQTAxXCIsICAgIFwiQlI5NzAwMzYwMzA1MDAwMDEwMDA5Nzk1NDkzUDFcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJDSFwiLCAyMSwgXCJGMDVBMTJcIiwgICAgICAgICAgICAgXCJDSDkzMDA3NjIwMTE2MjM4NTI5NTdcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJDUlwiLCAyMSwgXCJGMDNGMTRcIiwgICAgICAgICAgICAgXCJDUjA1MTUyMDIwMDEwMjYyODQwNjZcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJDWVwiLCAyOCwgXCJGMDNGMDVBMTZcIiwgICAgICAgICAgXCJDWTE3MDAyMDAxMjgwMDAwMDAxMjAwNTI3NjAwXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQ1pcIiwgMjQsIFwiRjA0RjA2RjEwXCIsICAgICAgICAgIFwiQ1o2NTA4MDAwMDAwMTkyMDAwMTQ1Mzk5XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiREVcIiwgMjIsIFwiRjA4RjEwXCIsICAgICAgICAgICAgIFwiREU4OTM3MDQwMDQ0MDUzMjAxMzAwMFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkRLXCIsIDE4LCBcIkYwNEYwOUYwMVwiLCAgICAgICAgICBcIkRLNTAwMDQwMDQ0MDExNjI0M1wiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkRPXCIsIDI4LCBcIlUwNEYyMFwiLCAgICAgICAgICAgICBcIkRPMjhCQUdSMDAwMDAwMDEyMTI0NTM2MTEzMjRcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJFRVwiLCAyMCwgXCJGMDJGMDJGMTFGMDFcIiwgICAgICAgXCJFRTM4MjIwMDIyMTAyMDE0NTY4NVwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkVTXCIsIDI0LCBcIkYwNEYwNEYwMUYwMUYxMFwiLCAgICBcIkVTOTEyMTAwMDQxODQ1MDIwMDA1MTMzMlwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkZJXCIsIDE4LCBcIkYwNkYwN0YwMVwiLCAgICAgICAgICBcIkZJMjExMjM0NTYwMDAwMDc4NVwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkZPXCIsIDE4LCBcIkYwNEYwOUYwMVwiLCAgICAgICAgICBcIkZPNjI2NDYwMDAwMTYzMTYzNFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkZSXCIsIDI3LCBcIkYwNUYwNUExMUYwMlwiLCAgICAgICBcIkZSMTQyMDA0MTAxMDA1MDUwMDAxM00wMjYwNlwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkdCXCIsIDIyLCBcIlUwNEYwNkYwOFwiLCAgICAgICAgICBcIkdCMjlOV0JLNjAxNjEzMzE5MjY4MTlcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJHRVwiLCAyMiwgXCJVMDJGMTZcIiwgICAgICAgICAgICAgXCJHRTI5TkIwMDAwMDAwMTAxOTA0OTE3XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiR0lcIiwgMjMsIFwiVTA0QTE1XCIsICAgICAgICAgICAgIFwiR0k3NU5XQkswMDAwMDAwMDcwOTk0NTNcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJHTFwiLCAxOCwgXCJGMDRGMDlGMDFcIiwgICAgICAgICAgXCJHTDg5NjQ3MTAwMDEwMDAyMDZcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJHUlwiLCAyNywgXCJGMDNGMDRBMTZcIiwgICAgICAgICAgXCJHUjE2MDExMDEyNTAwMDAwMDAwMTIzMDA2OTVcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJHVFwiLCAyOCwgXCJBMDRBMjBcIiwgICAgICAgICAgICAgXCJHVDgyVFJBSjAxMDIwMDAwMDAxMjEwMDI5NjkwXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiSFJcIiwgMjEsIFwiRjA3RjEwXCIsICAgICAgICAgICAgIFwiSFIxMjEwMDEwMDUxODYzMDAwMTYwXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiSFVcIiwgMjgsIFwiRjAzRjA0RjAxRjE1RjAxXCIsICAgIFwiSFU0MjExNzczMDE2MTExMTEwMTgwMDAwMDAwMFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIklFXCIsIDIyLCBcIlUwNEYwNkYwOFwiLCAgICAgICAgICBcIklFMjlBSUJLOTMxMTUyMTIzNDU2NzhcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJJTFwiLCAyMywgXCJGMDNGMDNGMTNcIiwgICAgICAgICAgXCJJTDYyMDEwODAwMDAwMDA5OTk5OTk5OVwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIklTXCIsIDI2LCBcIkYwNEYwMkYwNkYxMFwiLCAgICAgICBcIklTMTQwMTU5MjYwMDc2NTQ1NTEwNzMwMzM5XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiSVRcIiwgMjcsIFwiVTAxRjA1RjA1QTEyXCIsICAgICAgIFwiSVQ2MFgwNTQyODExMTAxMDAwMDAwMTIzNDU2XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiS1dcIiwgMzAsIFwiVTA0QTIyXCIsICAgICAgICAgICAgIFwiS1c4MUNCS1UwMDAwMDAwMDAwMDAxMjM0NTYwMTAxXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiS1pcIiwgMjAsIFwiRjAzQTEzXCIsICAgICAgICAgICAgIFwiS1o4NjEyNUtaVDUwMDQxMDAxMDBcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJMQlwiLCAyOCwgXCJGMDRBMjBcIiwgICAgICAgICAgICAgXCJMQjYyMDk5OTAwMDAwMDAxMDAxOTAxMjI5MTE0XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTENcIiwgMzIsIFwiVTA0RjI0XCIsICAgICAgICAgICAgIFwiTEMwN0hFTU0wMDAxMDAwMTAwMTIwMDEyMDAwMTMwMTVcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJMSVwiLCAyMSwgXCJGMDVBMTJcIiwgICAgICAgICAgICAgXCJMSTIxMDg4MTAwMDAyMzI0MDEzQUFcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJMVFwiLCAyMCwgXCJGMDVGMTFcIiwgICAgICAgICAgICAgXCJMVDEyMTAwMDAxMTEwMTAwMTAwMFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIkxVXCIsIDIwLCBcIkYwM0ExM1wiLCAgICAgICAgICAgICBcIkxVMjgwMDE5NDAwNjQ0NzUwMDAwXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTFZcIiwgMjEsIFwiVTA0QTEzXCIsICAgICAgICAgICAgIFwiTFY4MEJBTkswMDAwNDM1MTk1MDAxXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTUNcIiwgMjcsIFwiRjA1RjA1QTExRjAyXCIsICAgICAgIFwiTUM1ODExMjIyMDAwMDEwMTIzNDU2Nzg5MDMwXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTURcIiwgMjQsIFwiVTAyQTE4XCIsICAgICAgICAgICAgIFwiTUQyNEFHMDAwMjI1MTAwMDEzMTA0MTY4XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTUVcIiwgMjIsIFwiRjAzRjEzRjAyXCIsICAgICAgICAgIFwiTUUyNTUwNTAwMDAxMjM0NTY3ODk1MVwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIk1LXCIsIDE5LCBcIkYwM0ExMEYwMlwiLCAgICAgICAgICBcIk1LMDcyNTAxMjAwMDAwNTg5ODRcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJNUlwiLCAyNywgXCJGMDVGMDVGMTFGMDJcIiwgICAgICAgXCJNUjEzMDAwMjAwMDEwMTAwMDAxMjM0NTY3NTNcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJNVFwiLCAzMSwgXCJVMDRGMDVBMThcIiwgICAgICAgICAgXCJNVDg0TUFMVDAxMTAwMDAxMjM0NU1UTENBU1QwMDFTXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTVVcIiwgMzAsIFwiVTA0RjAyRjAyRjEyRjAzVTAzXCIsIFwiTVUxN0JPTU0wMTAxMTAxMDMwMzAwMjAwMDAwTVVSXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTkxcIiwgMTgsIFwiVTA0RjEwXCIsICAgICAgICAgICAgIFwiTkw5MUFCTkEwNDE3MTY0MzAwXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTk9cIiwgMTUsIFwiRjA0RjA2RjAxXCIsICAgICAgICAgIFwiTk85Mzg2MDExMTE3OTQ3XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiUEtcIiwgMjQsIFwiVTA0QTE2XCIsICAgICAgICAgICAgIFwiUEszNlNDQkwwMDAwMDAxMTIzNDU2NzAyXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiUExcIiwgMjgsIFwiRjA4RjE2XCIsICAgICAgICAgICAgIFwiUEw2MTEwOTAxMDE0MDAwMDA3MTIxOTgxMjg3NFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIlBTXCIsIDI5LCBcIlUwNEEyMVwiLCAgICAgICAgICAgICBcIlBTOTJQQUxTMDAwMDAwMDAwNDAwMTIzNDU2NzAyXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiUFRcIiwgMjUsIFwiRjA0RjA0RjExRjAyXCIsICAgICAgIFwiUFQ1MDAwMDIwMTIzMTIzNDU2Nzg5MDE1NFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIlJPXCIsIDI0LCBcIlUwNEExNlwiLCAgICAgICAgICAgICBcIlJPNDlBQUFBMUIzMTAwNzU5Mzg0MDAwMFwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIlJTXCIsIDIyLCBcIkYwM0YxM0YwMlwiLCAgICAgICAgICBcIlJTMzUyNjAwMDU2MDEwMDE2MTEzNzlcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJTQVwiLCAyNCwgXCJGMDJBMThcIiwgICAgICAgICAgICAgXCJTQTAzODAwMDAwMDA2MDgwMTAxNjc1MTlcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJTRVwiLCAyNCwgXCJGMDNGMTZGMDFcIiwgICAgICAgICAgXCJTRTQ1NTAwMDAwMDAwNTgzOTgyNTc0NjZcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJTSVwiLCAxOSwgXCJGMDVGMDhGMDJcIiwgICAgICAgICAgXCJTSTU2MjYzMzAwMDEyMDM5MDg2XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiU0tcIiwgMjQsIFwiRjA0RjA2RjEwXCIsICAgICAgICAgIFwiU0szMTEyMDAwMDAwMTk4NzQyNjM3NTQxXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiU01cIiwgMjcsIFwiVTAxRjA1RjA1QTEyXCIsICAgICAgIFwiU004NlUwMzIyNTA5ODAwMDAwMDAwMjcwMTAwXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiU1RcIiwgMjUsIFwiRjA4RjExRjAyXCIsICAgICAgICAgIFwiU1Q2ODAwMDEwMDAxMDA1MTg0NTMxMDExMlwiKSk7XG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIlRMXCIsIDIzLCBcIkYwM0YxNEYwMlwiLCAgICAgICAgICBcIlRMMzgwMDgwMDEyMzQ1Njc4OTEwMTU3XCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiVE5cIiwgMjQsIFwiRjAyRjAzRjEzRjAyXCIsICAgICAgIFwiVE41OTEwMDA2MDM1MTgzNTk4NDc4ODMxXCIpKTtcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiVFJcIiwgMjYsIFwiRjA1RjAxQTE2XCIsICAgICAgICAgIFwiVFIzMzAwMDYxMDA1MTk3ODY0NTc4NDEzMjZcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJWR1wiLCAyNCwgXCJVMDRGMTZcIiwgICAgICAgICAgICAgXCJWRzk2VlBWRzAwMDAwMTIzNDU2Nzg5MDFcIikpO1xuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJYS1wiLCAyMCwgXCJGMDRGMTBGMDJcIiwgICAgICAgICAgXCJYSzA1MTIxMjAxMjM0NTY3ODkwNlwiKSk7XG5cbiAgICAvLyBBbmdvbGFcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQU9cIiwgMjUsIFwiRjIxXCIsICAgICAgICAgICAgICAgIFwiQU82OTEyMzQ1Njc4OTAxMjM0NTY3ODkwMVwiKSk7XG4gICAgLy8gQnVya2luYVxuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJCRlwiLCAyNywgXCJGMjNcIiwgICAgICAgICAgICAgICAgXCJCRjIzMTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjNcIikpO1xuICAgIC8vIEJ1cnVuZGlcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQklcIiwgMTYsIFwiRjEyXCIsICAgICAgICAgICAgICAgIFwiQkk0MTEyMzQ1Njc4OTAxMlwiKSk7XG4gICAgLy8gQmVuaW5cbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQkpcIiwgMjgsIFwiRjI0XCIsICAgICAgICAgICAgICAgIFwiQkozOTEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzNFwiKSk7XG4gICAgLy8gSXZvcnlcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQ0lcIiwgMjgsIFwiVTAxRjIzXCIsICAgICAgICAgICAgIFwiQ0kxN0ExMjM0NTY3ODkwMTIzNDU2Nzg5MDEyM1wiKSk7XG4gICAgLy8gQ2FtZXJvblxuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJDTVwiLCAyNywgXCJGMjNcIiwgICAgICAgICAgICAgICAgXCJDTTkwMTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjNcIikpO1xuICAgIC8vIENhcGUgVmVyZGVcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiQ1ZcIiwgMjUsIFwiRjIxXCIsICAgICAgICAgICAgICAgIFwiQ1YzMDEyMzQ1Njc4OTAxMjM0NTY3ODkwMVwiKSk7XG4gICAgLy8gQWxnZXJpYVxuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJEWlwiLCAyNCwgXCJGMjBcIiwgICAgICAgICAgICAgICAgXCJEWjg2MTIzNDU2Nzg5MDEyMzQ1Njc4OTBcIikpO1xuICAgIC8vIElyYW5cbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiSVJcIiwgMjYsIFwiRjIyXCIsICAgICAgICAgICAgICAgIFwiSVI4NjEyMzQ1Njg3OTAxMjM0NTY3ODkwMTJcIikpO1xuICAgIC8vIEpvcmRhblxuICAgIGFkZFNwZWNpZmljYXRpb24obmV3IFNwZWNpZmljYXRpb24oXCJKT1wiLCAzMCwgXCJBMDRGMjJcIiwgICAgICAgICAgICAgXCJKTzE1QUFBQTEyMzQ1Njc4OTAxMjM0NTY3ODkwMTJcIikpO1xuICAgIC8vIE1hZGFnYXNjYXJcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTUdcIiwgMjcsIFwiRjIzXCIsICAgICAgICAgICAgICAgIFwiTUcxODEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzXCIpKTtcbiAgICAvLyBNYWxpXG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIk1MXCIsIDI4LCBcIlUwMUYyM1wiLCAgICAgICAgICAgICBcIk1MMTVBMTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjNcIikpO1xuICAgIC8vIE1vemFtYmlxdWVcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiTVpcIiwgMjUsIFwiRjIxXCIsICAgICAgICAgICAgICAgIFwiTVoyNTEyMzQ1Njc4OTAxMjM0NTY3ODkwMVwiKSk7XG4gICAgLy8gUXVhdGFyXG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIlFBXCIsIDI5LCBcIlUwNEEyMVwiLCAgICAgICAgICAgICBcIlFBMzBBQUFBMTIzNDU2Nzg5MDEyMzQ1Njc4OTAxXCIpKTtcbiAgICAvLyBTZW5lZ2FsXG4gICAgYWRkU3BlY2lmaWNhdGlvbihuZXcgU3BlY2lmaWNhdGlvbihcIlNOXCIsIDI4LCBcIlUwMUYyM1wiLCAgICAgICAgICAgICBcIlNONTJBMTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjNcIikpO1xuICAgIC8vIFVrcmFpbmVcbiAgICBhZGRTcGVjaWZpY2F0aW9uKG5ldyBTcGVjaWZpY2F0aW9uKFwiVUFcIiwgMjksIFwiRjI1XCIsICAgICAgICAgICAgICAgIFwiVUE1MTEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzNDVcIikpO1xuXG4gICAgdmFyIE5PTl9BTFBIQU5VTSA9IC9bXmEtekEtWjAtOV0vZyxcbiAgICAgICAgRVZFUllfRk9VUl9DSEFSUyA9LyguezR9KSg/ISQpL2c7XG5cbiAgICAvKipcbiAgICAgKiBVdGlsaXR5IGZ1bmN0aW9uIHRvIGNoZWNrIGlmIGEgdmFyaWFibGUgaXMgYSBTdHJpbmcuXG4gICAgICpcbiAgICAgKiBAcGFyYW0gdlxuICAgICAqIEByZXR1cm5zIHtib29sZWFufSB0cnVlIGlmIHRoZSBwYXNzZWQgdmFyaWFibGUgaXMgYSBTdHJpbmcsIGZhbHNlIG90aGVyd2lzZS5cbiAgICAgKi9cbiAgICBmdW5jdGlvbiBpc1N0cmluZyh2KXtcbiAgICAgICAgcmV0dXJuICh0eXBlb2YgdiA9PSAnc3RyaW5nJyB8fCB2IGluc3RhbmNlb2YgU3RyaW5nKTtcbiAgICB9XG5cbiAgICAvKipcbiAgICAgKiBDaGVjayBpZiBhbiBJQkFOIGlzIHZhbGlkLlxuICAgICAqXG4gICAgICogQHBhcmFtIHtTdHJpbmd9IGliYW4gdGhlIElCQU4gdG8gdmFsaWRhdGUuXG4gICAgICogQHJldHVybnMge2Jvb2xlYW59IHRydWUgaWYgdGhlIHBhc3NlZCBJQkFOIGlzIHZhbGlkLCBmYWxzZSBvdGhlcndpc2VcbiAgICAgKi9cbiAgICBleHBvcnRzLmlzVmFsaWQgPSBmdW5jdGlvbihpYmFuKXtcbiAgICAgICAgaWYgKCFpc1N0cmluZyhpYmFuKSl7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgaWJhbiA9IHRoaXMuZWxlY3Ryb25pY0Zvcm1hdChpYmFuKTtcbiAgICAgICAgdmFyIGNvdW50cnlTdHJ1Y3R1cmUgPSBjb3VudHJpZXNbaWJhbi5zbGljZSgwLDIpXTtcbiAgICAgICAgcmV0dXJuICEhY291bnRyeVN0cnVjdHVyZSAmJiBjb3VudHJ5U3RydWN0dXJlLmlzVmFsaWQoaWJhbik7XG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIENvbnZlcnQgYW4gSUJBTiB0byBhIEJCQU4uXG4gICAgICpcbiAgICAgKiBAcGFyYW0gaWJhblxuICAgICAqIEBwYXJhbSB7U3RyaW5nfSBbc2VwYXJhdG9yXSB0aGUgc2VwYXJhdG9yIHRvIHVzZSBiZXR3ZWVuIHRoZSBibG9ja3Mgb2YgdGhlIEJCQU4sIGRlZmF1bHRzIHRvICcgJ1xuICAgICAqIEByZXR1cm5zIHtzdHJpbmd8Kn1cbiAgICAgKi9cbiAgICBleHBvcnRzLnRvQkJBTiA9IGZ1bmN0aW9uKGliYW4sIHNlcGFyYXRvcil7XG4gICAgICAgIGlmICh0eXBlb2Ygc2VwYXJhdG9yID09ICd1bmRlZmluZWQnKXtcbiAgICAgICAgICAgIHNlcGFyYXRvciA9ICcgJztcbiAgICAgICAgfVxuICAgICAgICBpYmFuID0gdGhpcy5lbGVjdHJvbmljRm9ybWF0KGliYW4pO1xuICAgICAgICB2YXIgY291bnRyeVN0cnVjdHVyZSA9IGNvdW50cmllc1tpYmFuLnNsaWNlKDAsMildO1xuICAgICAgICBpZiAoIWNvdW50cnlTdHJ1Y3R1cmUpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcignTm8gY291bnRyeSB3aXRoIGNvZGUgJyArIGliYW4uc2xpY2UoMCwyKSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGNvdW50cnlTdHJ1Y3R1cmUudG9CQkFOKGliYW4sIHNlcGFyYXRvcik7XG4gICAgfTtcblxuICAgIC8qKlxuICAgICAqIENvbnZlcnQgdGhlIHBhc3NlZCBCQkFOIHRvIGFuIElCQU4gZm9yIHRoaXMgY291bnRyeSBzcGVjaWZpY2F0aW9uLlxuICAgICAqIFBsZWFzZSBub3RlIHRoYXQgPGk+XCJnZW5lcmF0aW9uIG9mIHRoZSBJQkFOIHNoYWxsIGJlIHRoZSBleGNsdXNpdmUgcmVzcG9uc2liaWxpdHkgb2YgdGhlIGJhbmsvYnJhbmNoIHNlcnZpY2luZyB0aGUgYWNjb3VudFwiPC9pPi5cbiAgICAgKiBUaGlzIG1ldGhvZCBpbXBsZW1lbnRzIHRoZSBwcmVmZXJyZWQgYWxnb3JpdGhtIGRlc2NyaWJlZCBpbiBodHRwOi8vZW4ud2lraXBlZGlhLm9yZy93aWtpL0ludGVybmF0aW9uYWxfQmFua19BY2NvdW50X051bWJlciNHZW5lcmF0aW5nX0lCQU5fY2hlY2tfZGlnaXRzXG4gICAgICpcbiAgICAgKiBAcGFyYW0gY291bnRyeUNvZGUgdGhlIGNvdW50cnkgb2YgdGhlIEJCQU5cbiAgICAgKiBAcGFyYW0gYmJhbiB0aGUgQkJBTiB0byBjb252ZXJ0IHRvIElCQU5cbiAgICAgKiBAcmV0dXJucyB7c3RyaW5nfSB0aGUgSUJBTlxuICAgICAqL1xuICAgIGV4cG9ydHMuZnJvbUJCQU4gPSBmdW5jdGlvbihjb3VudHJ5Q29kZSwgYmJhbil7XG4gICAgICAgIHZhciBjb3VudHJ5U3RydWN0dXJlID0gY291bnRyaWVzW2NvdW50cnlDb2RlXTtcbiAgICAgICAgaWYgKCFjb3VudHJ5U3RydWN0dXJlKSB7XG4gICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ05vIGNvdW50cnkgd2l0aCBjb2RlICcgKyBjb3VudHJ5Q29kZSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGNvdW50cnlTdHJ1Y3R1cmUuZnJvbUJCQU4odGhpcy5lbGVjdHJvbmljRm9ybWF0KGJiYW4pKTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogQ2hlY2sgdGhlIHZhbGlkaXR5IG9mIHRoZSBwYXNzZWQgQkJBTi5cbiAgICAgKlxuICAgICAqIEBwYXJhbSBjb3VudHJ5Q29kZSB0aGUgY291bnRyeSBvZiB0aGUgQkJBTlxuICAgICAqIEBwYXJhbSBiYmFuIHRoZSBCQkFOIHRvIGNoZWNrIHRoZSB2YWxpZGl0eSBvZlxuICAgICAqL1xuICAgIGV4cG9ydHMuaXNWYWxpZEJCQU4gPSBmdW5jdGlvbihjb3VudHJ5Q29kZSwgYmJhbil7XG4gICAgICAgIGlmICghaXNTdHJpbmcoYmJhbikpe1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIHZhciBjb3VudHJ5U3RydWN0dXJlID0gY291bnRyaWVzW2NvdW50cnlDb2RlXTtcbiAgICAgICAgcmV0dXJuIGNvdW50cnlTdHJ1Y3R1cmUgJiYgY291bnRyeVN0cnVjdHVyZS5pc1ZhbGlkQkJBTih0aGlzLmVsZWN0cm9uaWNGb3JtYXQoYmJhbikpO1xuICAgIH07XG5cbiAgICAvKipcbiAgICAgKlxuICAgICAqIEBwYXJhbSBpYmFuXG4gICAgICogQHBhcmFtIHNlcGFyYXRvclxuICAgICAqIEByZXR1cm5zIHtzdHJpbmd9XG4gICAgICovXG4gICAgZXhwb3J0cy5wcmludEZvcm1hdCA9IGZ1bmN0aW9uKGliYW4sIHNlcGFyYXRvcil7XG4gICAgICAgIGlmICh0eXBlb2Ygc2VwYXJhdG9yID09ICd1bmRlZmluZWQnKXtcbiAgICAgICAgICAgIHNlcGFyYXRvciA9ICcgJztcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gdGhpcy5lbGVjdHJvbmljRm9ybWF0KGliYW4pLnJlcGxhY2UoRVZFUllfRk9VUl9DSEFSUywgXCIkMVwiICsgc2VwYXJhdG9yKTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICpcbiAgICAgKiBAcGFyYW0gaWJhblxuICAgICAqIEByZXR1cm5zIHtzdHJpbmd9XG4gICAgICovXG4gICAgZXhwb3J0cy5lbGVjdHJvbmljRm9ybWF0ID0gZnVuY3Rpb24oaWJhbil7XG4gICAgICAgIHJldHVybiBpYmFuLnJlcGxhY2UoTk9OX0FMUEhBTlVNLCAnJykudG9VcHBlckNhc2UoKTtcbiAgICB9O1xuXG4gICAgLyoqXG4gICAgICogQW4gb2JqZWN0IGNvbnRhaW5pbmcgYWxsIHRoZSBrbm93biBJQkFOIHNwZWNpZmljYXRpb25zLlxuICAgICAqL1xuICAgIGV4cG9ydHMuY291bnRyaWVzID0gY291bnRyaWVzO1xuXG59KSk7XG4iLCIoZnVuY3Rpb24od2luZG93KSB7XG4gICAgdmFyIHJlID0ge1xuICAgICAgICBub3Rfc3RyaW5nOiAvW15zXS8sXG4gICAgICAgIG51bWJlcjogL1tkaWVmZ10vLFxuICAgICAgICBqc29uOiAvW2pdLyxcbiAgICAgICAgbm90X2pzb246IC9bXmpdLyxcbiAgICAgICAgdGV4dDogL15bXlxceDI1XSsvLFxuICAgICAgICBtb2R1bG86IC9eXFx4MjV7Mn0vLFxuICAgICAgICBwbGFjZWhvbGRlcjogL15cXHgyNSg/OihbMS05XVxcZCopXFwkfFxcKChbXlxcKV0rKVxcKSk/KFxcKyk/KDB8J1teJF0pPygtKT8oXFxkKyk/KD86XFwuKFxcZCspKT8oW2ItZ2lqb3N1eFhdKS8sXG4gICAgICAgIGtleTogL14oW2Etel9dW2Etel9cXGRdKikvaSxcbiAgICAgICAga2V5X2FjY2VzczogL15cXC4oW2Etel9dW2Etel9cXGRdKikvaSxcbiAgICAgICAgaW5kZXhfYWNjZXNzOiAvXlxcWyhcXGQrKVxcXS8sXG4gICAgICAgIHNpZ246IC9eW1xcK1xcLV0vXG4gICAgfVxuXG4gICAgZnVuY3Rpb24gc3ByaW50ZigpIHtcbiAgICAgICAgdmFyIGtleSA9IGFyZ3VtZW50c1swXSwgY2FjaGUgPSBzcHJpbnRmLmNhY2hlXG4gICAgICAgIGlmICghKGNhY2hlW2tleV0gJiYgY2FjaGUuaGFzT3duUHJvcGVydHkoa2V5KSkpIHtcbiAgICAgICAgICAgIGNhY2hlW2tleV0gPSBzcHJpbnRmLnBhcnNlKGtleSlcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gc3ByaW50Zi5mb3JtYXQuY2FsbChudWxsLCBjYWNoZVtrZXldLCBhcmd1bWVudHMpXG4gICAgfVxuXG4gICAgc3ByaW50Zi5mb3JtYXQgPSBmdW5jdGlvbihwYXJzZV90cmVlLCBhcmd2KSB7XG4gICAgICAgIHZhciBjdXJzb3IgPSAxLCB0cmVlX2xlbmd0aCA9IHBhcnNlX3RyZWUubGVuZ3RoLCBub2RlX3R5cGUgPSBcIlwiLCBhcmcsIG91dHB1dCA9IFtdLCBpLCBrLCBtYXRjaCwgcGFkLCBwYWRfY2hhcmFjdGVyLCBwYWRfbGVuZ3RoLCBpc19wb3NpdGl2ZSA9IHRydWUsIHNpZ24gPSBcIlwiXG4gICAgICAgIGZvciAoaSA9IDA7IGkgPCB0cmVlX2xlbmd0aDsgaSsrKSB7XG4gICAgICAgICAgICBub2RlX3R5cGUgPSBnZXRfdHlwZShwYXJzZV90cmVlW2ldKVxuICAgICAgICAgICAgaWYgKG5vZGVfdHlwZSA9PT0gXCJzdHJpbmdcIikge1xuICAgICAgICAgICAgICAgIG91dHB1dFtvdXRwdXQubGVuZ3RoXSA9IHBhcnNlX3RyZWVbaV1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGVsc2UgaWYgKG5vZGVfdHlwZSA9PT0gXCJhcnJheVwiKSB7XG4gICAgICAgICAgICAgICAgbWF0Y2ggPSBwYXJzZV90cmVlW2ldIC8vIGNvbnZlbmllbmNlIHB1cnBvc2VzIG9ubHlcbiAgICAgICAgICAgICAgICBpZiAobWF0Y2hbMl0pIHsgLy8ga2V5d29yZCBhcmd1bWVudFxuICAgICAgICAgICAgICAgICAgICBhcmcgPSBhcmd2W2N1cnNvcl1cbiAgICAgICAgICAgICAgICAgICAgZm9yIChrID0gMDsgayA8IG1hdGNoWzJdLmxlbmd0aDsgaysrKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoIWFyZy5oYXNPd25Qcm9wZXJ0eShtYXRjaFsyXVtrXSkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3Ioc3ByaW50ZihcIltzcHJpbnRmXSBwcm9wZXJ0eSAnJXMnIGRvZXMgbm90IGV4aXN0XCIsIG1hdGNoWzJdW2tdKSlcbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIGFyZyA9IGFyZ1ttYXRjaFsyXVtrXV1cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIGlmIChtYXRjaFsxXSkgeyAvLyBwb3NpdGlvbmFsIGFyZ3VtZW50IChleHBsaWNpdClcbiAgICAgICAgICAgICAgICAgICAgYXJnID0gYXJndlttYXRjaFsxXV1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgZWxzZSB7IC8vIHBvc2l0aW9uYWwgYXJndW1lbnQgKGltcGxpY2l0KVxuICAgICAgICAgICAgICAgICAgICBhcmcgPSBhcmd2W2N1cnNvcisrXVxuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChnZXRfdHlwZShhcmcpID09IFwiZnVuY3Rpb25cIikge1xuICAgICAgICAgICAgICAgICAgICBhcmcgPSBhcmcoKVxuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChyZS5ub3Rfc3RyaW5nLnRlc3QobWF0Y2hbOF0pICYmIHJlLm5vdF9qc29uLnRlc3QobWF0Y2hbOF0pICYmIChnZXRfdHlwZShhcmcpICE9IFwibnVtYmVyXCIgJiYgaXNOYU4oYXJnKSkpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IFR5cGVFcnJvcihzcHJpbnRmKFwiW3NwcmludGZdIGV4cGVjdGluZyBudW1iZXIgYnV0IGZvdW5kICVzXCIsIGdldF90eXBlKGFyZykpKVxuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChyZS5udW1iZXIudGVzdChtYXRjaFs4XSkpIHtcbiAgICAgICAgICAgICAgICAgICAgaXNfcG9zaXRpdmUgPSBhcmcgPj0gMFxuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIHN3aXRjaCAobWF0Y2hbOF0pIHtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSBcImJcIjpcbiAgICAgICAgICAgICAgICAgICAgICAgIGFyZyA9IGFyZy50b1N0cmluZygyKVxuICAgICAgICAgICAgICAgICAgICBicmVha1xuICAgICAgICAgICAgICAgICAgICBjYXNlIFwiY1wiOlxuICAgICAgICAgICAgICAgICAgICAgICAgYXJnID0gU3RyaW5nLmZyb21DaGFyQ29kZShhcmcpXG4gICAgICAgICAgICAgICAgICAgIGJyZWFrXG4gICAgICAgICAgICAgICAgICAgIGNhc2UgXCJkXCI6XG4gICAgICAgICAgICAgICAgICAgIGNhc2UgXCJpXCI6XG4gICAgICAgICAgICAgICAgICAgICAgICBhcmcgPSBwYXJzZUludChhcmcsIDEwKVxuICAgICAgICAgICAgICAgICAgICBicmVha1xuICAgICAgICAgICAgICAgICAgICBjYXNlIFwialwiOlxuICAgICAgICAgICAgICAgICAgICAgICAgYXJnID0gSlNPTi5zdHJpbmdpZnkoYXJnLCBudWxsLCBtYXRjaFs2XSA/IHBhcnNlSW50KG1hdGNoWzZdKSA6IDApXG4gICAgICAgICAgICAgICAgICAgIGJyZWFrXG4gICAgICAgICAgICAgICAgICAgIGNhc2UgXCJlXCI6XG4gICAgICAgICAgICAgICAgICAgICAgICBhcmcgPSBtYXRjaFs3XSA/IGFyZy50b0V4cG9uZW50aWFsKG1hdGNoWzddKSA6IGFyZy50b0V4cG9uZW50aWFsKClcbiAgICAgICAgICAgICAgICAgICAgYnJlYWtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSBcImZcIjpcbiAgICAgICAgICAgICAgICAgICAgICAgIGFyZyA9IG1hdGNoWzddID8gcGFyc2VGbG9hdChhcmcpLnRvRml4ZWQobWF0Y2hbN10pIDogcGFyc2VGbG9hdChhcmcpXG4gICAgICAgICAgICAgICAgICAgIGJyZWFrXG4gICAgICAgICAgICAgICAgICAgIGNhc2UgXCJnXCI6XG4gICAgICAgICAgICAgICAgICAgICAgICBhcmcgPSBtYXRjaFs3XSA/IHBhcnNlRmxvYXQoYXJnKS50b1ByZWNpc2lvbihtYXRjaFs3XSkgOiBwYXJzZUZsb2F0KGFyZylcbiAgICAgICAgICAgICAgICAgICAgYnJlYWtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSBcIm9cIjpcbiAgICAgICAgICAgICAgICAgICAgICAgIGFyZyA9IGFyZy50b1N0cmluZyg4KVxuICAgICAgICAgICAgICAgICAgICBicmVha1xuICAgICAgICAgICAgICAgICAgICBjYXNlIFwic1wiOlxuICAgICAgICAgICAgICAgICAgICAgICAgYXJnID0gKChhcmcgPSBTdHJpbmcoYXJnKSkgJiYgbWF0Y2hbN10gPyBhcmcuc3Vic3RyaW5nKDAsIG1hdGNoWzddKSA6IGFyZylcbiAgICAgICAgICAgICAgICAgICAgYnJlYWtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSBcInVcIjpcbiAgICAgICAgICAgICAgICAgICAgICAgIGFyZyA9IGFyZyA+Pj4gMFxuICAgICAgICAgICAgICAgICAgICBicmVha1xuICAgICAgICAgICAgICAgICAgICBjYXNlIFwieFwiOlxuICAgICAgICAgICAgICAgICAgICAgICAgYXJnID0gYXJnLnRvU3RyaW5nKDE2KVxuICAgICAgICAgICAgICAgICAgICBicmVha1xuICAgICAgICAgICAgICAgICAgICBjYXNlIFwiWFwiOlxuICAgICAgICAgICAgICAgICAgICAgICAgYXJnID0gYXJnLnRvU3RyaW5nKDE2KS50b1VwcGVyQ2FzZSgpXG4gICAgICAgICAgICAgICAgICAgIGJyZWFrXG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGlmIChyZS5qc29uLnRlc3QobWF0Y2hbOF0pKSB7XG4gICAgICAgICAgICAgICAgICAgIG91dHB1dFtvdXRwdXQubGVuZ3RoXSA9IGFyZ1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKHJlLm51bWJlci50ZXN0KG1hdGNoWzhdKSAmJiAoIWlzX3Bvc2l0aXZlIHx8IG1hdGNoWzNdKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgc2lnbiA9IGlzX3Bvc2l0aXZlID8gXCIrXCIgOiBcIi1cIlxuICAgICAgICAgICAgICAgICAgICAgICAgYXJnID0gYXJnLnRvU3RyaW5nKCkucmVwbGFjZShyZS5zaWduLCBcIlwiKVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgc2lnbiA9IFwiXCJcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBwYWRfY2hhcmFjdGVyID0gbWF0Y2hbNF0gPyBtYXRjaFs0XSA9PT0gXCIwXCIgPyBcIjBcIiA6IG1hdGNoWzRdLmNoYXJBdCgxKSA6IFwiIFwiXG4gICAgICAgICAgICAgICAgICAgIHBhZF9sZW5ndGggPSBtYXRjaFs2XSAtIChzaWduICsgYXJnKS5sZW5ndGhcbiAgICAgICAgICAgICAgICAgICAgcGFkID0gbWF0Y2hbNl0gPyAocGFkX2xlbmd0aCA+IDAgPyBzdHJfcmVwZWF0KHBhZF9jaGFyYWN0ZXIsIHBhZF9sZW5ndGgpIDogXCJcIikgOiBcIlwiXG4gICAgICAgICAgICAgICAgICAgIG91dHB1dFtvdXRwdXQubGVuZ3RoXSA9IG1hdGNoWzVdID8gc2lnbiArIGFyZyArIHBhZCA6IChwYWRfY2hhcmFjdGVyID09PSBcIjBcIiA/IHNpZ24gKyBwYWQgKyBhcmcgOiBwYWQgKyBzaWduICsgYXJnKVxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gb3V0cHV0LmpvaW4oXCJcIilcbiAgICB9XG5cbiAgICBzcHJpbnRmLmNhY2hlID0ge31cblxuICAgIHNwcmludGYucGFyc2UgPSBmdW5jdGlvbihmbXQpIHtcbiAgICAgICAgdmFyIF9mbXQgPSBmbXQsIG1hdGNoID0gW10sIHBhcnNlX3RyZWUgPSBbXSwgYXJnX25hbWVzID0gMFxuICAgICAgICB3aGlsZSAoX2ZtdCkge1xuICAgICAgICAgICAgaWYgKChtYXRjaCA9IHJlLnRleHQuZXhlYyhfZm10KSkgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICBwYXJzZV90cmVlW3BhcnNlX3RyZWUubGVuZ3RoXSA9IG1hdGNoWzBdXG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIGlmICgobWF0Y2ggPSByZS5tb2R1bG8uZXhlYyhfZm10KSkgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICBwYXJzZV90cmVlW3BhcnNlX3RyZWUubGVuZ3RoXSA9IFwiJVwiXG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIGlmICgobWF0Y2ggPSByZS5wbGFjZWhvbGRlci5leGVjKF9mbXQpKSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgIGlmIChtYXRjaFsyXSkge1xuICAgICAgICAgICAgICAgICAgICBhcmdfbmFtZXMgfD0gMVxuICAgICAgICAgICAgICAgICAgICB2YXIgZmllbGRfbGlzdCA9IFtdLCByZXBsYWNlbWVudF9maWVsZCA9IG1hdGNoWzJdLCBmaWVsZF9tYXRjaCA9IFtdXG4gICAgICAgICAgICAgICAgICAgIGlmICgoZmllbGRfbWF0Y2ggPSByZS5rZXkuZXhlYyhyZXBsYWNlbWVudF9maWVsZCkpICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBmaWVsZF9saXN0W2ZpZWxkX2xpc3QubGVuZ3RoXSA9IGZpZWxkX21hdGNoWzFdXG4gICAgICAgICAgICAgICAgICAgICAgICB3aGlsZSAoKHJlcGxhY2VtZW50X2ZpZWxkID0gcmVwbGFjZW1lbnRfZmllbGQuc3Vic3RyaW5nKGZpZWxkX21hdGNoWzBdLmxlbmd0aCkpICE9PSBcIlwiKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKChmaWVsZF9tYXRjaCA9IHJlLmtleV9hY2Nlc3MuZXhlYyhyZXBsYWNlbWVudF9maWVsZCkpICE9PSBudWxsKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGZpZWxkX2xpc3RbZmllbGRfbGlzdC5sZW5ndGhdID0gZmllbGRfbWF0Y2hbMV1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZWxzZSBpZiAoKGZpZWxkX21hdGNoID0gcmUuaW5kZXhfYWNjZXNzLmV4ZWMocmVwbGFjZW1lbnRfZmllbGQpKSAhPT0gbnVsbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBmaWVsZF9saXN0W2ZpZWxkX2xpc3QubGVuZ3RoXSA9IGZpZWxkX21hdGNoWzFdXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0aHJvdyBuZXcgU3ludGF4RXJyb3IoXCJbc3ByaW50Zl0gZmFpbGVkIHRvIHBhcnNlIG5hbWVkIGFyZ3VtZW50IGtleVwiKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBTeW50YXhFcnJvcihcIltzcHJpbnRmXSBmYWlsZWQgdG8gcGFyc2UgbmFtZWQgYXJndW1lbnQga2V5XCIpXG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgbWF0Y2hbMl0gPSBmaWVsZF9saXN0XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBhcmdfbmFtZXMgfD0gMlxuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBpZiAoYXJnX25hbWVzID09PSAzKSB7XG4gICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihcIltzcHJpbnRmXSBtaXhpbmcgcG9zaXRpb25hbCBhbmQgbmFtZWQgcGxhY2Vob2xkZXJzIGlzIG5vdCAoeWV0KSBzdXBwb3J0ZWRcIilcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgcGFyc2VfdHJlZVtwYXJzZV90cmVlLmxlbmd0aF0gPSBtYXRjaFxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhyb3cgbmV3IFN5bnRheEVycm9yKFwiW3NwcmludGZdIHVuZXhwZWN0ZWQgcGxhY2Vob2xkZXJcIilcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIF9mbXQgPSBfZm10LnN1YnN0cmluZyhtYXRjaFswXS5sZW5ndGgpXG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHBhcnNlX3RyZWVcbiAgICB9XG5cbiAgICB2YXIgdnNwcmludGYgPSBmdW5jdGlvbihmbXQsIGFyZ3YsIF9hcmd2KSB7XG4gICAgICAgIF9hcmd2ID0gKGFyZ3YgfHwgW10pLnNsaWNlKDApXG4gICAgICAgIF9hcmd2LnNwbGljZSgwLCAwLCBmbXQpXG4gICAgICAgIHJldHVybiBzcHJpbnRmLmFwcGx5KG51bGwsIF9hcmd2KVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIGhlbHBlcnNcbiAgICAgKi9cbiAgICBmdW5jdGlvbiBnZXRfdHlwZSh2YXJpYWJsZSkge1xuICAgICAgICByZXR1cm4gT2JqZWN0LnByb3RvdHlwZS50b1N0cmluZy5jYWxsKHZhcmlhYmxlKS5zbGljZSg4LCAtMSkudG9Mb3dlckNhc2UoKVxuICAgIH1cblxuICAgIGZ1bmN0aW9uIHN0cl9yZXBlYXQoaW5wdXQsIG11bHRpcGxpZXIpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5KG11bHRpcGxpZXIgKyAxKS5qb2luKGlucHV0KVxuICAgIH1cblxuICAgIC8qKlxuICAgICAqIGV4cG9ydCB0byBlaXRoZXIgYnJvd3NlciBvciBub2RlLmpzXG4gICAgICovXG4gICAgaWYgKHR5cGVvZiBleHBvcnRzICE9PSBcInVuZGVmaW5lZFwiKSB7XG4gICAgICAgIGV4cG9ydHMuc3ByaW50ZiA9IHNwcmludGZcbiAgICAgICAgZXhwb3J0cy52c3ByaW50ZiA9IHZzcHJpbnRmXG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICB3aW5kb3cuc3ByaW50ZiA9IHNwcmludGZcbiAgICAgICAgd2luZG93LnZzcHJpbnRmID0gdnNwcmludGZcblxuICAgICAgICBpZiAodHlwZW9mIGRlZmluZSA9PT0gXCJmdW5jdGlvblwiICYmIGRlZmluZS5hbWQpIHtcbiAgICAgICAgICAgIGRlZmluZShmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4ge1xuICAgICAgICAgICAgICAgICAgICBzcHJpbnRmOiBzcHJpbnRmLFxuICAgICAgICAgICAgICAgICAgICB2c3ByaW50ZjogdnNwcmludGZcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KVxuICAgICAgICB9XG4gICAgfVxufSkodHlwZW9mIHdpbmRvdyA9PT0gXCJ1bmRlZmluZWRcIiA/IHRoaXMgOiB3aW5kb3cpO1xuIiwiIWZ1bmN0aW9uKHJvb3QsIGZhY3RvcnkpIHtcbiAgICBcImZ1bmN0aW9uXCIgPT0gdHlwZW9mIGRlZmluZSAmJiBkZWZpbmUuYW1kID8gLy8gQU1ELiBSZWdpc3RlciBhcyBhbiBhbm9ueW1vdXMgbW9kdWxlIHVubGVzcyBhbWRNb2R1bGVJZCBpcyBzZXRcbiAgICBkZWZpbmUoW10sIGZ1bmN0aW9uKCkge1xuICAgICAgICByZXR1cm4gcm9vdC5zdmc0ZXZlcnlib2R5ID0gZmFjdG9yeSgpO1xuICAgIH0pIDogXCJvYmplY3RcIiA9PSB0eXBlb2YgZXhwb3J0cyA/IG1vZHVsZS5leHBvcnRzID0gZmFjdG9yeSgpIDogcm9vdC5zdmc0ZXZlcnlib2R5ID0gZmFjdG9yeSgpO1xufSh0aGlzLCBmdW5jdGlvbigpIHtcbiAgICAvKiEgc3ZnNGV2ZXJ5Ym9keSB2Mi4wLjMgfCBnaXRodWIuY29tL2pvbmF0aGFudG5lYWwvc3ZnNGV2ZXJ5Ym9keSAqL1xuICAgIGZ1bmN0aW9uIGVtYmVkKHN2ZywgdGFyZ2V0KSB7XG4gICAgICAgIC8vIGlmIHRoZSB0YXJnZXQgZXhpc3RzXG4gICAgICAgIGlmICh0YXJnZXQpIHtcbiAgICAgICAgICAgIC8vIGNyZWF0ZSBhIGRvY3VtZW50IGZyYWdtZW50IHRvIGhvbGQgdGhlIGNvbnRlbnRzIG9mIHRoZSB0YXJnZXRcbiAgICAgICAgICAgIHZhciBmcmFnbWVudCA9IGRvY3VtZW50LmNyZWF0ZURvY3VtZW50RnJhZ21lbnQoKSwgdmlld0JveCA9ICFzdmcuZ2V0QXR0cmlidXRlKFwidmlld0JveFwiKSAmJiB0YXJnZXQuZ2V0QXR0cmlidXRlKFwidmlld0JveFwiKTtcbiAgICAgICAgICAgIC8vIGNvbmRpdGlvbmFsbHkgc2V0IHRoZSB2aWV3Qm94IG9uIHRoZSBzdmdcbiAgICAgICAgICAgIHZpZXdCb3ggJiYgc3ZnLnNldEF0dHJpYnV0ZShcInZpZXdCb3hcIiwgdmlld0JveCk7XG4gICAgICAgICAgICAvLyBjb3B5IHRoZSBjb250ZW50cyBvZiB0aGUgY2xvbmUgaW50byB0aGUgZnJhZ21lbnRcbiAgICAgICAgICAgIGZvciAoLy8gY2xvbmUgdGhlIHRhcmdldFxuICAgICAgICAgICAgdmFyIGNsb25lID0gdGFyZ2V0LmNsb25lTm9kZSghMCk7IGNsb25lLmNoaWxkTm9kZXMubGVuZ3RoOyApIHtcbiAgICAgICAgICAgICAgICBmcmFnbWVudC5hcHBlbmRDaGlsZChjbG9uZS5maXJzdENoaWxkKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIC8vIGFwcGVuZCB0aGUgZnJhZ21lbnQgaW50byB0aGUgc3ZnXG4gICAgICAgICAgICBzdmcuYXBwZW5kQ2hpbGQoZnJhZ21lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGZ1bmN0aW9uIGxvYWRyZWFkeXN0YXRlY2hhbmdlKHhocikge1xuICAgICAgICAvLyBsaXN0ZW4gdG8gY2hhbmdlcyBpbiB0aGUgcmVxdWVzdFxuICAgICAgICB4aHIub25yZWFkeXN0YXRlY2hhbmdlID0gZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAvLyBpZiB0aGUgcmVxdWVzdCBpcyByZWFkeVxuICAgICAgICAgICAgaWYgKDQgPT09IHhoci5yZWFkeVN0YXRlKSB7XG4gICAgICAgICAgICAgICAgLy8gZ2V0IHRoZSBjYWNoZWQgaHRtbCBkb2N1bWVudFxuICAgICAgICAgICAgICAgIHZhciBjYWNoZWREb2N1bWVudCA9IHhoci5fY2FjaGVkRG9jdW1lbnQ7XG4gICAgICAgICAgICAgICAgLy8gZW5zdXJlIHRoZSBjYWNoZWQgaHRtbCBkb2N1bWVudCBiYXNlZCBvbiB0aGUgeGhyIHJlc3BvbnNlXG4gICAgICAgICAgICAgICAgY2FjaGVkRG9jdW1lbnQgfHwgKGNhY2hlZERvY3VtZW50ID0geGhyLl9jYWNoZWREb2N1bWVudCA9IGRvY3VtZW50LmltcGxlbWVudGF0aW9uLmNyZWF0ZUhUTUxEb2N1bWVudChcIlwiKSwgXG4gICAgICAgICAgICAgICAgY2FjaGVkRG9jdW1lbnQuYm9keS5pbm5lckhUTUwgPSB4aHIucmVzcG9uc2VUZXh0LCB4aHIuX2NhY2hlZFRhcmdldCA9IHt9KSwgLy8gY2xlYXIgdGhlIHhociBlbWJlZHMgbGlzdCBhbmQgZW1iZWQgZWFjaCBpdGVtXG4gICAgICAgICAgICAgICAgeGhyLl9lbWJlZHMuc3BsaWNlKDApLm1hcChmdW5jdGlvbihpdGVtKSB7XG4gICAgICAgICAgICAgICAgICAgIC8vIGdldCB0aGUgY2FjaGVkIHRhcmdldFxuICAgICAgICAgICAgICAgICAgICB2YXIgdGFyZ2V0ID0geGhyLl9jYWNoZWRUYXJnZXRbaXRlbS5pZF07XG4gICAgICAgICAgICAgICAgICAgIC8vIGVuc3VyZSB0aGUgY2FjaGVkIHRhcmdldFxuICAgICAgICAgICAgICAgICAgICB0YXJnZXQgfHwgKHRhcmdldCA9IHhoci5fY2FjaGVkVGFyZ2V0W2l0ZW0uaWRdID0gY2FjaGVkRG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoaXRlbS5pZCkpLCBcbiAgICAgICAgICAgICAgICAgICAgLy8gZW1iZWQgdGhlIHRhcmdldCBpbnRvIHRoZSBzdmdcbiAgICAgICAgICAgICAgICAgICAgZW1iZWQoaXRlbS5zdmcsIHRhcmdldCk7XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0sIC8vIHRlc3QgdGhlIHJlYWR5IHN0YXRlIGNoYW5nZSBpbW1lZGlhdGVseVxuICAgICAgICB4aHIub25yZWFkeXN0YXRlY2hhbmdlKCk7XG4gICAgfVxuICAgIGZ1bmN0aW9uIHN2ZzRldmVyeWJvZHkocmF3b3B0cykge1xuICAgICAgICBmdW5jdGlvbiBvbmludGVydmFsKCkge1xuICAgICAgICAgICAgLy8gd2hpbGUgdGhlIGluZGV4IGV4aXN0cyBpbiB0aGUgbGl2ZSA8dXNlPiBjb2xsZWN0aW9uXG4gICAgICAgICAgICBmb3IgKC8vIGdldCB0aGUgY2FjaGVkIDx1c2U+IGluZGV4XG4gICAgICAgICAgICB2YXIgaW5kZXggPSAwOyBpbmRleCA8IHVzZXMubGVuZ3RoOyApIHtcbiAgICAgICAgICAgICAgICAvLyBnZXQgdGhlIGN1cnJlbnQgPHVzZT5cbiAgICAgICAgICAgICAgICB2YXIgdXNlID0gdXNlc1tpbmRleF0sIHN2ZyA9IHVzZS5wYXJlbnROb2RlO1xuICAgICAgICAgICAgICAgIGlmIChzdmcgJiYgL3N2Zy9pLnRlc3Qoc3ZnLm5vZGVOYW1lKSkge1xuICAgICAgICAgICAgICAgICAgICB2YXIgc3JjID0gdXNlLmdldEF0dHJpYnV0ZShcInhsaW5rOmhyZWZcIik7XG4gICAgICAgICAgICAgICAgICAgIGlmIChwb2x5ZmlsbCAmJiAoIW9wdHMudmFsaWRhdGUgfHwgb3B0cy52YWxpZGF0ZShzcmMsIHN2ZywgdXNlKSkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIC8vIHJlbW92ZSB0aGUgPHVzZT4gZWxlbWVudFxuICAgICAgICAgICAgICAgICAgICAgICAgc3ZnLnJlbW92ZUNoaWxkKHVzZSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAvLyBwYXJzZSB0aGUgc3JjIGFuZCBnZXQgdGhlIHVybCBhbmQgaWRcbiAgICAgICAgICAgICAgICAgICAgICAgIHZhciBzcmNTcGxpdCA9IHNyYy5zcGxpdChcIiNcIiksIHVybCA9IHNyY1NwbGl0LnNoaWZ0KCksIGlkID0gc3JjU3BsaXQuam9pbihcIiNcIik7XG4gICAgICAgICAgICAgICAgICAgICAgICAvLyBpZiB0aGUgbGluayBpcyBleHRlcm5hbFxuICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHVybC5sZW5ndGgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAvLyBnZXQgdGhlIGNhY2hlZCB4aHIgcmVxdWVzdFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHZhciB4aHIgPSByZXF1ZXN0c1t1cmxdO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGVuc3VyZSB0aGUgeGhyIHJlcXVlc3QgZXhpc3RzXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgeGhyIHx8ICh4aHIgPSByZXF1ZXN0c1t1cmxdID0gbmV3IFhNTEh0dHBSZXF1ZXN0KCksIHhoci5vcGVuKFwiR0VUXCIsIHVybCksIHhoci5zZW5kKCksIFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHhoci5fZW1iZWRzID0gW10pLCAvLyBhZGQgdGhlIHN2ZyBhbmQgaWQgYXMgYW4gaXRlbSB0byB0aGUgeGhyIGVtYmVkcyBsaXN0XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgeGhyLl9lbWJlZHMucHVzaCh7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHN2Zzogc3ZnLFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZDogaWRcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KSwgLy8gcHJlcGFyZSB0aGUgeGhyIHJlYWR5IHN0YXRlIGNoYW5nZSBldmVudFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGxvYWRyZWFkeXN0YXRlY2hhbmdlKHhocik7XG4gICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIGVtYmVkIHRoZSBsb2NhbCBpZCBpbnRvIHRoZSBzdmdcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbWJlZChzdmcsIGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKGlkKSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAvLyBpbmNyZWFzZSB0aGUgaW5kZXggd2hlbiB0aGUgcHJldmlvdXMgdmFsdWUgd2FzIG5vdCBcInZhbGlkXCJcbiAgICAgICAgICAgICAgICAgICAgKytpbmRleDtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICAvLyBjb250aW51ZSB0aGUgaW50ZXJ2YWxcbiAgICAgICAgICAgIHJlcXVlc3RBbmltYXRpb25GcmFtZShvbmludGVydmFsLCA2Nyk7XG4gICAgICAgIH1cbiAgICAgICAgdmFyIHBvbHlmaWxsLCBvcHRzID0gT2JqZWN0KHJhd29wdHMpLCBuZXdlcklFVUEgPSAvXFxiVHJpZGVudFxcL1s1NjddXFxifFxcYk1TSUUgKD86OXwxMClcXC4wXFxiLywgd2Via2l0VUEgPSAvXFxiQXBwbGVXZWJLaXRcXC8oXFxkKylcXGIvLCBvbGRlckVkZ2VVQSA9IC9cXGJFZGdlXFwvMTJcXC4oXFxkKylcXGIvO1xuICAgICAgICBwb2x5ZmlsbCA9IFwicG9seWZpbGxcIiBpbiBvcHRzID8gb3B0cy5wb2x5ZmlsbCA6IG5ld2VySUVVQS50ZXN0KG5hdmlnYXRvci51c2VyQWdlbnQpIHx8IChuYXZpZ2F0b3IudXNlckFnZW50Lm1hdGNoKG9sZGVyRWRnZVVBKSB8fCBbXSlbMV0gPCAxMDU0NyB8fCAobmF2aWdhdG9yLnVzZXJBZ2VudC5tYXRjaCh3ZWJraXRVQSkgfHwgW10pWzFdIDwgNTM3O1xuICAgICAgICAvLyBjcmVhdGUgeGhyIHJlcXVlc3RzIG9iamVjdFxuICAgICAgICB2YXIgcmVxdWVzdHMgPSB7fSwgcmVxdWVzdEFuaW1hdGlvbkZyYW1lID0gd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSB8fCBzZXRUaW1lb3V0LCB1c2VzID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeVRhZ05hbWUoXCJ1c2VcIik7XG4gICAgICAgIC8vIGNvbmRpdGlvbmFsbHkgc3RhcnQgdGhlIGludGVydmFsIGlmIHRoZSBwb2x5ZmlsbCBpcyBhY3RpdmVcbiAgICAgICAgcG9seWZpbGwgJiYgb25pbnRlcnZhbCgpO1xuICAgIH1cbiAgICByZXR1cm4gc3ZnNGV2ZXJ5Ym9keTtcbn0pOyIsIi8qXG4gKiBVbmlsZW5kIEF1dG9jb21wbGV0ZVxuICovXG5cbi8qXG5AdG9kbyBzdXBwb3J0IEFKQVggcmVzdWx0c1xuQHRvZG8gZmluZXNzZSBrZXlib2FyZCB1cC9kb3duIG9uIHJlc3VsdHNcbiovXG5cbnZhciAkID0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ2pRdWVyeSddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnalF1ZXJ5J10gOiBudWxsKVxuXG4vLyBDYXNlLWluc2Vuc2l0aXZlIHNlbGVjdG9yIGA6Q29udGFpbnMoKWBcbmpRdWVyeS5leHByWyc6J10uQ29udGFpbnMgPSBmdW5jdGlvbihhLCBpLCBtKSB7XG4gIHJldHVybiBqUXVlcnkoYSkudGV4dCgpLnRvVXBwZXJDYXNlKCkuaW5kZXhPZihtWzNdLnRvVXBwZXJDYXNlKCkpID49IDA7XG59O1xuXG4vLyBBdXRvQ29tcGxldGUgTGFuZ3VhZ2VcbnZhciBEaWN0aW9uYXJ5ID0gcmVxdWlyZSgnRGljdGlvbmFyeScpXG52YXIgQVVUT0NPTVBMRVRFX0xBTkcgPSByZXF1aXJlKCcuLi8uLi8uLi9sYW5nL0F1dG9Db21wbGV0ZS5sYW5nLmpzb24nKVxudmFyIF9fID0gbmV3IERpY3Rpb25hcnkoQVVUT0NPTVBMRVRFX0xBTkcpXG5cbi8qXG4gKiBBdXRvQ29tcGxldGVcbiAqIEBjbGFzc1xuICovXG4vLyB2YXIgYXV0b0NvbXBsZXRlID0gbmV3IEF1dG9Db21wbGV0ZSggZWxlbU9yU2VsZWN0b3IsIHsuLn0pO1xudmFyIEF1dG9Db21wbGV0ZSA9IGZ1bmN0aW9uICggZWxlbSwgb3B0aW9ucyApIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgLypcbiAgICogT3B0aW9uc1xuICAgKi9cbiAgc2VsZi5vcHRpb25zID0gJC5leHRlbmQoe1xuICAgIGlucHV0OiBlbGVtLCAvLyBUaGUgaW5wdXQgZWxlbWVudCB0byB0YWtlIHRoZSB0ZXh0IGlucHV0XG4gICAgdGFyZ2V0OiBmYWxzZSwgLy8gVGhlIHRhcmdldCBlbGVtZW50IHRvIHB1dCB0aGUgcmVzdWx0c1xuICAgIGFqYXhVcmw6IGZhbHNlLCAvLyBBbiBhamF4IFVSTCB0byBzZW5kIHRoZSB0ZXJtIHJlY2VpdmUgcmVzdWx0cyBmcm9tLiBJZiBgZmFsc2VgLCBsb29rcyBpbiB0YXJnZXQgZWxlbWVudCBmb3IgdGhlIHRleHRcbiAgICBkZWxheTogMjAwLCAvLyBBIGRlbGF5IHRvIHdhaXQgYmVmb3JlIHNlYXJjaGluZyBmb3IgdGhlIHRlcm1cbiAgICBtaW5UZXJtTGVuZ3RoOiAzLCAvLyBUaGUgbWluaW11bSBjaGFyYWN0ZXIgbGVuZ3RoIG9mIGEgdGVybSB0byBmaW5kXG4gICAgc2hvd0VtcHR5OiBmYWxzZSwgLy8gU2hvdyBhdXRvY29tcGxldGUgd2l0aCBtZXNzYWdlcyBpZiBubyByZXN1bHRzIGZvdW5kXG4gICAgc2hvd1NpbmdsZTogdHJ1ZSAvLyBTaG93IHRoZSBhdXRvY29tcGxldGUgaWYgb25seSBvbmUgcmVzdWx0IGZvdW5kXG4gIH0sIG9wdGlvbnMpXG5cbiAgLy8gUHJvcGVydGllc1xuICAvLyAtLSBVc2UgalF1ZXJ5IHRvIHNlbGVjdCBlbGVtLCBkaXN0aW5ndWlzaCBiZXR3ZWVuIHN0cmluZywgSFRNTEVsZW1lbnQgYW5kIGpRdWVyeSBPYmplY3RcbiAgc2VsZi4kaW5wdXQgPSAkKHNlbGYub3B0aW9ucy5pbnB1dClcbiAgc2VsZi4kdGFyZ2V0ID0gJChzZWxmLm9wdGlvbnMudGFyZ2V0KVxuXG4gIC8vIE5lZWRzIGFuIGlucHV0IGVsZW1lbnQgdG8gYmUgdmFsaWRcbiAgaWYgKCBzZWxmLiRpbnB1dC5sZW5ndGggPT09IDAgKSByZXR1cm4gc2VsZi5lcnJvcignaW5wdXQgZWxlbWVudCBkb2VzblxcJ3QgZXhpc3QnKVxuXG4gIC8vIENyZWF0ZSBhIG5ldyB0YXJnZXQgZWxlbWVudCBmb3IgdGhlIHJlc3VsdHNcbiAgaWYgKCAhc2VsZi5vcHRpb25zLnRhcmdldCB8fCBzZWxmLiR0YXJnZXQubGVuZ3RoID09PSAwICkge1xuICAgIHNlbGYuJHRhcmdldCA9ICQoJzxkaXYgY2xhc3M9XCJhdXRvY29tcGxldGVcIj48dWwgY2xhc3M9XCJhdXRvY29tcGxldGUtcmVzdWx0c1wiPjwvdWw+PC9kaXY+JylcbiAgICBzZWxmLiRpbnB1dC5hZnRlcihzZWxmLiR0YXJnZXQpXG4gIH1cblxuICAvLyBHZXQgYmFzZSBlbGVtXG4gIHNlbGYuaW5wdXQgPSBzZWxmLiRpbnB1dFswXVxuICBzZWxmLnRhcmdldCA9IHNlbGYuJHRhcmdldFswXVxuICBzZWxmLnRpbWVyID0gdW5kZWZpbmVkXG5cbiAgLypcbiAgICogRXZlbnRzXG4gICAqL1xuICAvLyBUeXBlIGludG8gdGhlIGlucHV0IGVsZW1cbiAgc2VsZi4kaW5wdXQub24oJ2tleWRvd24nLCBmdW5jdGlvbiAoIGV2ZW50ICkge1xuICAgIGNsZWFyVGltZW91dChzZWxmLnRpbWVyKVxuICAgIHNlbGYudGltZXIgPSBzZXRUaW1lb3V0KCBzZWxmLmZpbmRUZXJtLCBzZWxmLm9wdGlvbnMuZGVsYXkgKVxuXG4gICAgLy8gRXNjYXBlIGtleSAtIGhpZGVcbiAgICBpZiAoIGV2ZW50LndoaWNoID09PSAyNyApIHtcbiAgICAgIHNlbGYuaGlkZSgpXG4gICAgfVxuICB9KVxuXG4gIC8vIEhpZGUgYXV0b2NvbXBsZXRlXG4gIHNlbGYuJGlucHV0Lm9uKCdhdXRvY29tcGxldGUtaGlkZScsIGZ1bmN0aW9uICggZXZlbnQgKSB7XG4gICAgLy8gY29uc29sZS5sb2coJ2F1dG9jb21wbGV0ZS1oaWRlJywgc2VsZi5pbnB1dClcbiAgICBzZWxmLmhpZGUoKVxuICB9KVxuXG4gIC8vIENsaWNrIHJlc3VsdCB0byBjb21wbGV0ZSB0aGUgaW5wdXRcbiAgc2VsZi4kdGFyZ2V0Lm9uKCdjbGljaycsICcuYXV0b2NvbXBsZXRlLXJlc3VsdHMgYScsIGZ1bmN0aW9uICggZXZlbnQgKSB7XG4gICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgIHNlbGYuJGlucHV0LnZhbCgkKHRoaXMpLnRleHQoKSlcbiAgICBzZWxmLmhpZGUoKVxuICB9KVxuXG4gIC8vIEtleWJvYXJkIG9wZXJhdGlvbnMgb24gcmVzdWx0c1xuICBzZWxmLiR0YXJnZXQub24oJ2tleWRvd24nLCAnLmF1dG9jb21wbGV0ZS1yZXN1bHRzIGE6Zm9jdXMnLCBmdW5jdGlvbiAoIGV2ZW50ICkge1xuICAgIC8vIE1vdmUgYmV0d2VlbiByZXN1bHRzIGFuZCBpbnB1dFxuICAgIC8vIEB0b2RvIGZpbmVzc2Uga2V5Ym9hcmQgdXAvZG93biBvbiByZXN1bHRzXG4gICAgLy8gLS0gVXAga2V5XG4gICAgaWYgKCBldmVudC53aGljaCA9PT0gMzggKSB7XG4gICAgICAvLyBGb2N1cyBvbiBpbnB1dFxuICAgICAgaWYgKCAkKHRoaXMpLnBhcmVudHMoJ2xpJykuaXMoJy5hdXRvY29tcGxldGUtcmVzdWx0cyBsaTplcSgwKScpICkge1xuICAgICAgICBzZWxmLiRpbnB1dC5mb2N1cygpXG5cbiAgICAgIC8vIEZvY3VzIG9uIHByZXZpb3VzIHJlc3VsdCBhbmNob3JcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgICAgJCh0aGlzKS5wYXJlbnRzKCdsaScpLnByZXYoJ2xpJykuZmluZCgnYScpLmZvY3VzKClcbiAgICAgIH1cblxuICAgIC8vIC0tIERvd24ga2V5XG4gICAgfSBlbHNlIGlmICggZXZlbnQud2hpY2ggPT09IDQwICkge1xuICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgICAgJCh0aGlzKS5wYXJlbnRzKCdsaScpLm5leHQoJ2xpJykuZmluZCgnYScpLmZvY3VzKClcblxuICAgIC8vIC0tIFByZXNzIGVzYyB0byBjbGVhciB0aGUgYXV0b2NvbXBsZXRlIGFuZCBnbyBiYWNrIHRvIHRoZSBzZWFyY2hcbiAgICB9IGVsc2UgaWYgKCBldmVudC53aGljaCA9PT0gMjcgKSB7XG4gICAgICBzZWxmLiRpbnB1dC5mb2N1cygpXG4gICAgICBzZWxmLmhpZGUoKVxuXG4gICAgLy8gLS0gUHJlc3MgZW50ZXIgb3IgcmlnaHQgYXJyb3cgb24gaGlnaGxpZ2h0ZWQgcmVzdWx0IHRvIGNvbXBsZXRlIHRoZSBpbnB1dFxuICAgIH0gZWxzZSBpZiAoIGV2ZW50LndoaWNoID09PSAzOSB8fCBldmVudC53aGljaCA9PT0gMTMgKSB7XG4gICAgICBzZWxmLiRpbnB1dC52YWwoJCh0aGlzKS50ZXh0KCkpLmZvY3VzKClcbiAgICAgIHNlbGYuaGlkZSgpXG4gICAgfVxuICB9KVxuXG4gIC8qXG4gICAqIE1ldGhvZHNcbiAgICovXG4gIC8vIEZpbmQgYSB0ZXJtXG4gIHNlbGYuZmluZFRlcm0gPSBmdW5jdGlvbiAodGVybSkge1xuICAgIHZhciByZXN1bHRzID0gW11cblxuICAgIC8vIE5vIHRlcm0gZ2l2ZW4/IEFzc3VtZSB0ZXJtIGlzIHZhbCgpIG9mIGVsZW1cbiAgICBpZiAoIHR5cGVvZiB0ZXJtID09PSAndW5kZWZpbmVkJyB8fCB0ZXJtID09PSBmYWxzZSApIHRlcm0gPSBzZWxmLiRpbnB1dC52YWwoKVxuXG4gICAgLy8gVGVybSBsZW5ndGggbm90IGxvbmcgZW5vdWdoLCBhYm9ydFxuICAgIGlmICggdGVybS5sZW5ndGggPCBzZWxmLm9wdGlvbnMubWluVGVybUxlbmd0aCApIHJldHVyblxuXG4gICAgLy8gUGVyZm9ybSBhamF4IHNlYXJjaFxuICAgIGlmICggc2VsZi5vcHRpb25zLmFqYXhVcmwgKSB7XG4gICAgICBzZWxmLmZpbmRUZXJtVmlhQWpheCh0ZXJtKVxuXG4gICAgLy8gUGVyZm9ybSBzZWFyY2ggd2l0aGluIHRhcmdldCBmb3IgYW4gZWxlbWVudCdzIHdob3NlIGNoaWxkcmVuIGNvbnRhaW4gdGhlIHRleHRcbiAgICB9IGVsc2Uge1xuICAgICAgcmVzdWx0cyA9IHNlbGYuJHRhcmdldC5maW5kKCcuYXV0b2NvbXBsZXRlLXJlc3VsdHMgbGk6Q29udGFpbnMoXFwnJyt0ZXJtKydcXCcpJyk7XG4gICAgICBzZWxmLnNob3dSZXN1bHRzKHRlcm0sIHJlc3VsdHMpXG4gICAgfVxuICB9XG5cbiAgLy8gRmluZCBhIHRlcm0gdmlhIEFKQVhcbiAgc2VsZi5maW5kVGVybVZpYUFqYXggPSBmdW5jdGlvbiAodGVybSkge1xuICAgICQuYWpheCh7XG4gICAgICB1cmw6IHNlbGYub3B0aW9ucy5hamF4VXJsLFxuICAgICAgZGF0YToge1xuICAgICAgICB0ZXJtOiB0ZXJtXG4gICAgICB9LFxuICAgICAgc3VjY2VzczogZnVuY3Rpb24gKGRhdGEsIHRleHRTdGF0dXMsIHhocikge1xuICAgICAgICBpZiAoIHRleHRTdGF0dXMgPT09ICdzdWNjZXNzJyApIHtcbiAgICAgICAgICAvLyBAdG9kbyBzdXBwb3J0IEFKQVggcmVzdWx0c1xuICAgICAgICAgIC8vIEBub3RlIEFKQVggc2hvdWxkIHJldHVybiBKU09OIG9iamVjdCBhcyBhcnJheSwgZS5nLjpcbiAgICAgICAgICAvLyAgICAgICBbIFwiQ29tbWVudCDDp2EgdmEgP1wiLCBcIkNvbW1lbnQgP1wiLCBcIldhbnQgdG8gbGVhdmUgYSBjb21tZW50P1wiIF1cbiAgICAgICAgICAvLyAgICAgICBBdXRvQ29tcGxldGUgd2lsbCBhdXRvbWF0aWNhbGx5IGhpZ2hsaWdodCB0aGUgcmVzdWx0cyB0ZXh0IGFzIG5lY2Vzc2FyeVxuICAgICAgICAgIHNlbGYuc2hvd1Jlc3VsdHModGVybSwgZGF0YSlcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBzZWxmLndhcm5pbmcoJ0FqYXggRXJyb3I6ICcrdGV4dFN0YXR1cywgeGhyKVxuICAgICAgICB9XG4gICAgICB9LFxuICAgICAgZXJyb3I6IGZ1bmN0aW9uICh0ZXh0U3RhdHVzLCB4aHIpIHtcbiAgICAgICAgc2VsZi53YXJuaW5nKCdBamF4IEVycm9yOiAnK3RleHRTdGF0dXMsIHhocilcbiAgICAgIH1cbiAgICB9KVxuICB9XG5cbiAgLy8gRGlzcGxheSB0aGUgcmVzdWx0c1xuICBzZWxmLnNob3dSZXN1bHRzID0gZnVuY3Rpb24gKHRlcm0sIHJlc3VsdHMpIHtcbiAgICB2YXIgcmVUZXJtID0gbmV3IFJlZ0V4cCgnKCcrc2VsZi5yZUVzY2FwZSh0ZXJtKSsnKScsICdnaScpXG5cbiAgICAvLyBSZW1vdmUgYW55IG1lc3NhZ2VzXG4gICAgc2VsZi4kdGFyZ2V0LmZpbmQoJ2xpLmF1dG9jb21wbGV0ZS1tZXNzYWdlJykucmVtb3ZlKClcblxuICAgIC8vIFBvcHVsYXRlIHRoZSB0YXJnZXQgZWxlbWVudCByZXN1bHRzIGFzIEhUTUxcbiAgICAvLyAtLSBJZiBhamF4VXJsIGlzIHNldCwgYXNzdW1lIHJlc3VsdHMgd2lsbCBiZSBhcnJheVxuICAgIGlmICggc2VsZi5vcHRpb25zLmFqYXhVcmwgKSB7XG4gICAgICB2YXIgcmVzdWx0c0hUTUwgPSAnJztcbiAgICAgICQocmVzdWx0cykuZWFjaCggZnVuY3Rpb24gKGksIGl0ZW0pIHtcbiAgICAgICAgcmVzdWx0c0hUTUwgKz0gJzxsaT48YSBocmVmPVwiamF2YXNjcmlwdDp2b2lkKDApXCIgdGFiaW5kZXg9XCIxXCI+JyArIHNlbGYuaGlnaGxpZ2h0VGVybSh0ZXJtLCBpdGVtKSArICc8L2E+PC9saT4nXG4gICAgICB9KVxuICAgICAgc2VsZi4kdGFyZ2V0LmZpbmQoJy5hdXRvY29tcGxldGUtcmVzdWx0cycpLmh0bWwocmVzdWx0c0hUTUwpXG5cbiAgICAgIC8vIFNlbGVjdCBhbGwgcmVzdWx0cyBhcyBqUXVlcnkgY29sbGVjdGlvbiBmb3IgZnVydGhlciBvcGVyYXRpb25zXG4gICAgICByZXN1bHRzID0gc2VsZi4kdGFyZ2V0LmZpbmQoJy5hdXRvY29tcGxldGUtcmVzdWx0cyBsaScpXG5cbiAgICAvLyAtLSBJZiBhamF4VXJsIGlzIGZhbHNlLCBhc3N1bWUgdGFyZ2V0IGFscmVhZHkgY29udGFpbnMgaXRlbXMsIGFuZCB0aGF0IHJlc3VsdHNcbiAgICAvLyAgICBpcyBhIGpRdWVyeSBjb2xsZWN0aW9uIG9mIHRob3NlIHJlc3VsdCBlbGVtZW50cyB3aGljaCBtYXRjaCB0aGUgZm91bmQgdGVybVxuICAgIH0gZWxzZSB7XG4gICAgICBzZWxmLnJlbW92ZUhpZ2hsaWdodHMoKVxuICAgICAgc2VsZi4kdGFyZ2V0LmZpbmQoJy5hdXRvY29tcGxldGUtcmVzdWx0cyBsaScpLmhpZGUoKVxuICAgIH1cblxuICAgIC8vIE5vIHJlc3VsdHNcbiAgICBpZiAoIHJlc3VsdHMubGVuZ3RoID09PSAwICkge1xuXG4gICAgICAvLyBTaG93IG5vIHJlc3VsdHMgbWVzc2FnZVxuICAgICAgaWYgKCBzZWxmLm9wdGlvbnMuc2hvd0VtcHR5ICkge1xuICAgICAgICBpZiAoIHNlbGYuJHRhcmdldC5maW5kKCcuYXV0b2NvbXBsZXRlLXJlc3VsdHMgbGkuZW1wdHknKS5sZW5ndGggPT09IDAgKSB7XG4gICAgICAgICAgc2VsZi4kdGFyZ2V0LmZpbmQoJy5hdXRvY29tcGxldGUtcmVzdWx0cycpLmFwcGVuZCgnPGxpIGNsYXNzPVwiYXV0b2NvbXBsZXRlLW1lc3NhZ2Ugbm8tcmVzdWx0c1wiPicrX18uX18oJ05vIHJlc3VsdHMgZm91bmQhJywgJ25vUmVzdWx0cycpKyc8L2xpPicpXG4gICAgICAgIH1cbiAgICAgICAgc2VsZi4kdGFyZ2V0LmZpbmQoJy5hdXRvY29tcGxldGUtcmVzdWx0cyBsaS5uby1yZXN1bHRzJykuc2hvdygpXG4gICAgICB9IGVsc2Uge1xuICAgICAgICBzZWxmLmhpZGUoKVxuICAgICAgICByZXR1cm5cbiAgICAgIH1cblxuICAgIC8vIFJlc3VsdHMhXG4gICAgfSBlbHNlIHtcbiAgICAgIC8vIEhpZGUgaWYgb25seSAxIHJlc3VsdCBhdmFpbGFibGUgYW5kIG9wdGlvbnMuc2hvd1NpbmdsZSBpcyBkaXNhYmxlZFxuICAgICAgaWYgKCByZXN1bHRzLmxlbmd0aCA9PT0gMSAmJiAhc2VsZi5vcHRpb25zLnNob3dTaW5nbGUgKSB7XG4gICAgICAgIHNlbGYuaGlkZSgpXG4gICAgICAgIHJldHVyblxuICAgICAgfVxuXG4gICAgICAvLyBTaG93IHRoZSByZXN1bHRzXG4gICAgICBzZWxmLmhpZ2hsaWdodFJlc3VsdHModGVybSwgcmVzdWx0cylcbiAgICAgIHJlc3VsdHMuc2hvdygpXG4gICAgfVxuXG4gICAgc2VsZi5zaG93KClcbiAgfVxuXG4gIC8vIEVzY2FwZSBhIHN0cmluZyBmb3IgcmVnZXhwIHB1cnBvc2VzXG4gIC8vIFNlZTogaHR0cDovL3N0YWNrb3ZlcmZsb3cuY29tL2EvNjk2OTQ4NlxuICBzZWxmLnJlRXNjYXBlID0gZnVuY3Rpb24gKHN0cikge1xuICAgIHJldHVybiBzdHIucmVwbGFjZSgvW1xcLVxcW1xcXVxcL1xce1xcfVxcKFxcKVxcKlxcK1xcP1xcLlxcXFxcXF5cXCRcXHxdL2csIFwiXFxcXCQmXCIpXG4gIH1cblxuICAvLyBBZGQgaGlnaGxpZ2h0cyB0byB0aGUgcmVzdWx0c1xuICBzZWxmLmhpZ2hsaWdodFJlc3VsdHMgPSBmdW5jdGlvbiAodGVybSwgcmVzdWx0cykge1xuICAgIHJlc3VsdHMuZWFjaCggZnVuY3Rpb24gKGksIGl0ZW0pIHtcbiAgICAgIHZhciB0ZXh0ID0gJCh0aGlzKS5maW5kKCdhJykudGV4dCgpXG4gICAgICB2YXIgbmV3VGV4dCA9IHNlbGYuaGlnaGxpZ2h0VGVybSh0ZXJtLCB0ZXh0KVxuICAgICAgJCh0aGlzKS5maW5kKCdhJykuaHRtbChuZXdUZXh0KVxuICAgIH0pXG4gIH1cblxuICAvLyBBZGQgaGlnaGxpZ2h0IHRvIHN0cmluZ1xuICBzZWxmLmhpZ2hsaWdodFRlcm0gPSBmdW5jdGlvbiAodGVybSwgc3RyKSB7XG4gICAgdmFyIHJlVGVybSA9IG5ldyBSZWdFeHAoICcoJytzZWxmLnJlRXNjYXBlKHRlcm0pKycpJywgJ2dpJylcbiAgICByZXR1cm4gc3RyLnJlcGxhY2UocmVUZXJtLCAnPHNwYW4gY2xhc3M9XCJoaWdobGlnaHRcIj4kMTwvc3Bhbj4nKVxuICB9XG5cbiAgLy8gUmVtb3ZlIGhpZ2hsaWdodHMgZnJvbSB0aGUgdGV4dFxuICBzZWxmLnJlbW92ZUhpZ2hsaWdodHMgPSBmdW5jdGlvbiAoKSB7XG4gICAgc2VsZi4kdGFyZ2V0LmZpbmQoJy5oaWdobGlnaHQnKS5jb250ZW50cygpLnVud3JhcCgpXG4gIH1cblxuICAvLyBTaG93IHRoZSBhdXRvY29tcGxldGVcbiAgc2VsZi5zaG93ID0gZnVuY3Rpb24gKCkge1xuICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZyggJ3Nob3cgQXV0b0NvbXBsZXRlJylcblxuICAgIHNlbGYuJHRhcmdldC5zaG93KClcblxuICAgIC8vIEFjY2Vzc2liaWxpdHlcbiAgICBzZWxmLiR0YXJnZXQuYXR0cignYXJpYS1oaWRkZW4nLCAnZmFsc2UnKS5maW5kKCcuYXV0b2NvbXBsZXRlLXJlc3VsdHMgbGkgYScpLmF0dHIoJ3RhYmluZGV4JywgMSlcbiAgfVxuXG4gIC8vIEhpZGUgdGhlIGF1dG9jb21wbGV0ZVxuICBzZWxmLmhpZGUgPSBmdW5jdGlvbiAoKSB7XG4gICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCAnaGlkZSBBdXRvQ29tcGxldGUnKVxuXG4gICAgY2xlYXJUaW1lb3V0KHNlbGYudGltZXIpXG4gICAgc2VsZi4kdGFyZ2V0LmhpZGUoKVxuXG4gICAgLy8gQWNjZXNpYmlsaXR5XG4gICAgc2VsZi4kdGFyZ2V0LmF0dHIoJ2FyaWEtaGlkZGVuJywgJ3RydWUnKS5maW5kKCcuYXV0b2NvbXBsZXRlLXJlc3VsdHMgbGkgYScpLmF0dHIoJ3RhYmluZGV4JywgLTEpXG4gIH1cblxuICAvLyBIYXJkIGVycm9yXG4gIHNlbGYuZXJyb3IgPSBmdW5jdGlvbiAoKSB7XG4gICAgdGhyb3cgbmV3IEVycm9yLmFwcGx5KHNlbGYsIGFyZ3VtZW50cylcbiAgICByZXR1cm5cbiAgfVxuXG4gIC8vIFNvZnQgZXJyb3IgKGNvbnNvbGUgd2FybmluZylcbiAgc2VsZi53YXJuaW5nID0gZnVuY3Rpb24gKCkge1xuICAgIC8vIGlmICggd2luZG93LmNvbnNvbGUgKSBpZiAoIGNvbnNvbGUubG9nICkge1xuICAgIC8vICAgY29uc29sZS5sb2coJ1tBdXRvQ29tcGxldGUgRXJyb3JdJylcbiAgICAvLyAgIGNvbnNvbGUubG9nLmFwcGx5KHNlbGYsIGFyZ3VtZW50cylcbiAgICAvLyB9XG4gIH1cblxuICAvKlxuICAgKiBJbml0aWFsaXNlXG4gICAqL1xuICAvLyBBc3NpZ24gZGlyZWN0IEF1dG9Db21wbGV0ZSByZWZlcmVuY2UgdG8gdGhlIGlucHV0IGFuZCB0YXJnZXQgZWxlbXNcbiAgc2VsZi5pbnB1dC5BdXRvQ29tcGxldGUgPSBzZWxmXG4gIHNlbGYudGFyZ2V0LkF1dG9Db21wbGV0ZSA9IHNlbGZcblxuICAvLyBSZXR1cm4gdGhlIEF1dG9Db21wbGV0ZSBvYmplY3RcbiAgcmV0dXJuIHNlbGZcbn1cblxuLy8gbW9kdWxlLmV4cG9ydHMgPSBBdXRvQ29tcGxldGVcbm1vZHVsZS5leHBvcnRzID0gQXV0b0NvbXBsZXRlXG4iLCIvKlxuICogRGFzaGJvYXJkIFBhbmVsXG4gKi9cblxudmFyICQgPSAodHlwZW9mIHdpbmRvdyAhPT0gXCJ1bmRlZmluZWRcIiA/IHdpbmRvd1snalF1ZXJ5J10gOiB0eXBlb2YgZ2xvYmFsICE9PSBcInVuZGVmaW5lZFwiID8gZ2xvYmFsWydqUXVlcnknXSA6IG51bGwpXG52YXIgVXRpbGl0eSA9IHJlcXVpcmUoJ1V0aWxpdHknKVxudmFyIEVsZW1lbnRBdHRyc09iamVjdCA9IHJlcXVpcmUoJ0VsZW1lbnRBdHRyc09iamVjdCcpXG5cbnZhciBEYXNoYm9hcmRQYW5lbCA9IGZ1bmN0aW9uIChlbGVtLCBvcHRpb25zKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICBzZWxmLiRlbGVtID0gJChlbGVtKVxuICBpZiAoc2VsZi4kZWxlbS5sZW5ndGggPT09IDApIHJldHVyblxuXG4gIC8vIE5lZWRzIGFuIElEIG51bWJlclxuICBzZWxmLmlkID0gc2VsZi4kZWxlbS5hdHRyKCdpZCcpIHx8IHJhbmRvbVN0cmluZygpXG4gIGlmICghc2VsZi4kZWxlbS5hdHRyKCdpZCcpKSBzZWxmLiRlbGVtLmF0dHIoJ2lkJywgc2VsZi5pZClcbiAgc2VsZi50aXRsZSA9IHNlbGYuJGVsZW0uZmluZCgnLmRhc2hib2FyZC1wYW5lbC10aXRsZScpLnRleHQoKVxuXG4gIC8vIFNldHRpbmdzXG4gIHNlbGYuc2V0dGluZ3MgPSAkLmV4dGVuZCh7XG4gICAgZHJhZ2dhYmxlOiB0cnVlXG4gIH0sIEVsZW1lbnRBdHRyc09iamVjdChlbGVtLCB7XG4gICAgZHJhZ2dhYmxlOiAnZGF0YS1kcmFnZ2FibGUnXG4gIH0pLCBvcHRpb25zKVxuXG4gIC8vIEFzc2lnbiB0aGUgY2xhc3MgdG8gc2hvdyB0aGUgVUkgZnVuY3Rpb25hbGl0eSBoYXMgYmVlbiBhcHBsaWVkXG4gIHNlbGYuJGVsZW0uYWRkQ2xhc3MoJ3VpLWRhc2hib2FyZC1wYW5lbCcpXG5cbiAgLy8gU2hvdyB0aGUgcGFuZWxcbiAgc2VsZi5zaG93ID0gZnVuY3Rpb24gKCkge1xuICAgIHZhciBzZWxmID0gdGhpc1xuICAgIHNlbGYuJGVsZW0ucmVtb3ZlQ2xhc3MoJ3VpLWRhc2hib2FyZC1wYW5lbC1oaWRkZW4nKVxuICAgIHNlbGYuZ2V0VG9nZ2xlcygpLnJlbW92ZUNsYXNzKCd1aS1kYXNoYm9hcmQtcGFuZWwtaGlkZGVuJylcbiAgICBzZWxmLnJlZnJlc2hMYXlvdXQoKVxuICB9XG5cbiAgLy8gSGlkZSB0aGUgcGFuZWxcbiAgc2VsZi5oaWRlID0gZnVuY3Rpb24gKCkge1xuICAgIHZhciBzZWxmID0gdGhpc1xuICAgIHNlbGYuJGVsZW0uYWRkQ2xhc3MoJ3VpLWRhc2hib2FyZC1wYW5lbC1oaWRkZW4nKVxuICAgIHNlbGYuZ2V0VG9nZ2xlcygpLmFkZENsYXNzKCd1aS1kYXNoYm9hcmQtcGFuZWwtaGlkZGVuJylcbiAgICBzZWxmLnJlZnJlc2hMYXlvdXQoKVxuICB9XG5cbiAgLy8gVG9nZ2xlIHRoZSBwYW5lbFxuICBzZWxmLnRvZ2dsZSA9IGZ1bmN0aW9uICgpIHtcbiAgICB2YXIgc2VsZiA9IHRoaXNcbiAgICBpZiAoc2VsZi4kZWxlbS5pcygnLnVpLWRhc2hib2FyZC1wYW5lbC1oaWRkZW4nKSkge1xuICAgICAgc2VsZi5zaG93KClcbiAgICB9IGVsc2Uge1xuICAgICAgc2VsZi5oaWRlKClcbiAgICB9XG4gIH1cblxuICAvLyBSZWZyZXNoIGFueSBsYXlvdXQgbW9kdWxlc1xuICBzZWxmLnJlZnJlc2hMYXlvdXQgPSBmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgICAvLyBSZWZyZXNoIHBhY2tlcnlcbiAgICBpZiAoc2VsZi4kZWxlbS5wYXJlbnRzKCdbZGF0YS1wYWNrZXJ5XScpLmxlbmd0aCA+IDApIHtcbiAgICAgIHNlbGYuJGVsZW0ucGFyZW50cygnW2RhdGEtcGFja2VyeV0nKS5wYWNrZXJ5KClcbiAgICB9XG4gIH1cblxuICAvLyBHZXQgYW55IGl0ZW0gd2hpY2ggdG9nZ2xlcyB0aGlzIGRhc2hib2FyZCBwYW5lbFxuICBzZWxmLmdldFRvZ2dsZXMgPSBmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG4gICAgcmV0dXJuICQoJ1tocmVmPVwiIycgKyBzZWxmLmlkICsgJ1wiXS5kYXNoYm9hcmQtcGFuZWwtdG9nZ2xlJylcbiAgfVxuXG4gIC8vIFRyaWdnZXIgaGlkZVxuICBpZiAoc2VsZi4kZWxlbS5pcygnLnVpLWRhc2hib2FyZC1wYW5lbC1oaWRkZW4nKSkge1xuICAgIHNlbGYuaGlkZSgpXG4gIH0gZWxzZSB7XG4gICAgc2VsZi5zaG93KClcbiAgfVxuXG4gIHNlbGYuJGVsZW1bMF0uRGFzaGJvYXJkUGFuZWwgPSBzZWxmXG4gIHJldHVybiBzZWxmXG59XG5cbi8qXG4gKiBqUXVlcnkgUGx1Z2luXG4gKi9cbiQuZm4udWlEYXNoYm9hcmRQYW5lbCA9IGZ1bmN0aW9uIChvcCkge1xuICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgbmV3IERhc2hib2FyZFBhbmVsKGVsZW0sIG9wKVxuICB9KVxufVxuXG4vKlxuICogalF1ZXJ5IEluaXRpYWxpc2F0aW9uXG4gKi9cbiQoZG9jdW1lbnQpXG4gIC5vbigncmVhZHknLCBmdW5jdGlvbiAoKSB7XG4gICAgJCgnLmRhc2hib2FyZC1wYW5lbCwgW2RhdGEtZGFzaGJvYXJkcGFuZWxdJykudWlEYXNoYm9hcmRQYW5lbCgpXG4gIH0pXG5cbiAgLy8gVG9nZ2xlIHRoZSBwYW5lbCB2aWEgdGhlIHRvZ2dsZSBvcHRpb25cbiAgLm9uKFV0aWxpdHkuY2xpY2tFdmVudCwgJy5kYXNoYm9hcmQtcGFuZWwtdG9nZ2xlJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgIHZhciAkcGFuZWwgPSAkKCQodGhpcykuYXR0cignaHJlZicpKVxuICAgIGlmICgkcGFuZWwubGVuZ3RoID4gMCkgJHBhbmVsWzBdLkRhc2hib2FyZFBhbmVsLnRvZ2dsZSgpXG4gIH0pXG5cbiAgLy8gSGlkZSB0aGUgcGFuZWwgdmlhIHRoZSBjbG9zZSBidXR0b25cbiAgLm9uKFV0aWxpdHkuY2xpY2tFdmVudCwgJy5kYXNoYm9hcmQtcGFuZWwgLmJ0bi1jbG9zZScsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICB2YXIgJHBhbmVsID0gJCh0aGlzKS5wYXJlbnRzKCcuZGFzaGJvYXJkLXBhbmVsJylcbiAgICAkcGFuZWxbMF0uRGFzaGJvYXJkUGFuZWwuaGlkZSgpXG4gIH0pXG5cbm1vZHVsZS5leHBvcnRzID0gRGFzaGJvYXJkUGFuZWxcbiIsIi8qXG4gKiBVbmlsZW5kIEZpbGUgQXR0YWNoXG4gKiBFbmFibGVzIGV4dGVuZGVkIFVJIGZvciBhdHRhY2hpbmcvcmVtb3ZpbmcgZmlsZXMgdG8gYSBmb3JtXG4gKi9cblxuLy8gQFRPRE8gaW50ZWdyYXRlIERpY3Rpb25hcnlcbi8vIEBUT0RPIG1heSBuZWVkIEFKQVggZnVuY3Rpb25hbGl0eVxuXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbClcbnZhciBFbGVtZW50QXR0cnNPYmplY3QgPSByZXF1aXJlKCdFbGVtZW50QXR0cnNPYmplY3QnKVxudmFyIFRlbXBsYXRpbmcgPSByZXF1aXJlKCdUZW1wbGF0aW5nJylcblxuZnVuY3Rpb24gcmFuZG9tU3RyaW5nIChzdHJpbmdMZW5ndGgpIHtcbiAgdmFyIG91dHB1dCA9ICcnXG4gIHZhciBjaGFycyA9ICdhYmNkZWZnaGlqa2xtbm9wcXJzdHV2d3h5ekFCQ0RFRkdISUpLTE1OT1BRUlNUVVZXWFlaJ1xuICBzdHJpbmdMZW5ndGggPSBzdHJpbmdMZW5ndGggfHwgOFxuICBmb3IgKHZhciBpID0gMDsgaSA8IHN0cmluZ0xlbmd0aDsgaSsrKSB7XG4gICAgb3V0cHV0ICs9IGNoYXJzLmNoYXJBdChNYXRoLmZsb29yKE1hdGgucmFuZG9tKCkgKiBjaGFycy5sZW5ndGgpKVxuICB9XG4gIHJldHVybiBvdXRwdXRcbn1cblxuZnVuY3Rpb24gZ2V0RmlsZVNpemVVbml0cyAoZmlsZVNpemVJbkJ5dGVzKSB7XG4gIGlmIChmaWxlU2l6ZUluQnl0ZXMgPCAxMDI0KSB7XG4gICAgcmV0dXJuICdCJ1xuICB9IGVsc2UgaWYgKGZpbGVTaXplSW5CeXRlcyA+PSAxMDI0ICYmIGZpbGVTaXplSW5CeXRlcyA8IDEwNDg1NzYpIHtcbiAgICByZXR1cm4gJ0tCJ1xuICB9IGVsc2UgaWYgKGZpbGVTaXplSW5CeXRlcyA+PSAxMDQ4NTc2ICYmIGZpbGVTaXplSW5CeXRlcyA8IDEwNzM3NDE4MjQpIHtcbiAgICByZXR1cm4gJ01CJ1xuICB9IGVsc2UgaWYgKGZpbGVTaXplSW5CeXRlcyA+PSAxMDczNzQxODI0KSB7XG4gICAgcmV0dXJuICdHQidcbiAgfVxufVxuXG5mdW5jdGlvbiBnZXRGaWxlU2l6ZVdpdGhVbml0cyAoZmlsZVNpemVJbkJ5dGVzKSB7XG4gIHZhciB1bml0cyA9IGdldEZpbGVTaXplVW5pdHMoZmlsZVNpemVJbkJ5dGVzKVxuICB2YXIgZmlsZVNpemUgPSBmaWxlU2l6ZUluQnl0ZXNcblxuICBzd2l0Y2ggKHVuaXRzKSB7XG4gICAgY2FzZSAnQic6XG4gICAgICByZXR1cm4gZmlsZVNpemUgKyAnICcgKyB1bml0c1xuXG4gICAgY2FzZSAnS0InOlxuICAgICAgZmlsZVNpemUgPSBmaWxlU2l6ZSAvIDEwMjRcbiAgICAgIHJldHVybiBNYXRoLmZsb29yKGZpbGVTaXplKSArICcgJyArIHVuaXRzXG5cbiAgICBjYXNlICdNQic6XG4gICAgICBmaWxlU2l6ZSA9IGZpbGVTaXplIC8gMTA0ODU3NlxuICAgICAgcmV0dXJuIGZpbGVTaXplLnRvRml4ZWQoMSkgKyAnICcgKyB1bml0c1xuXG4gICAgY2FzZSAnR0InOlxuICAgICAgZmlsZVNpemUgPSBmaWxlU2l6ZSAvIDEwNzM3NDE4MjRcbiAgICAgIHJldHVybiBmaWxlU2l6ZS50b0ZpeGVkKDIpICsgJyAnICsgdW5pdHNcbiAgfVxufVxuXG52YXIgRmlsZUF0dGFjaCA9IGZ1bmN0aW9uIChlbGVtLCBvcHRpb25zKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICBzZWxmLiRlbGVtID0gJChlbGVtKVxuXG4gIC8vIEVycm9yXG4gIGlmIChzZWxmLiRlbGVtLmxlbmd0aCA9PT0gMCkgcmV0dXJuXG5cbiAgLy8gU2V0dGluZ3NcbiAgLy8gLS0gRGVmYXVsdHNcbiAgc2VsZi5zZXR0aW5ncyA9ICQuZXh0ZW5kKHtcbiAgICAvLyBQcm9wZXJ0aWVzXG4gICAgbWF4RmlsZXM6IDAsXG4gICAgbWF4U2l6ZTogKDEwMjQgKiAxMDI0ICogOCksXG4gICAgZmlsZVR5cGVzOiAncGRmIGpwZyBqcGVnIHBuZyBkb2MgZG9jeCcsXG4gICAgaW5wdXROYW1lOiAnZmlsZWF0dGFjaCcsXG5cbiAgICAvLyBUZW1wbGF0ZVxuICAgIHRlbXBsYXRlczoge1xuICAgICAgZmlsZUl0ZW06ICc8bGFiZWwgY2xhc3M9XCJ1aS1maWxlYXR0YWNoLWl0ZW1cIj48c3BhbiBjbGFzcz1cImxhYmVsXCI+e3sgZmlsZU5hbWUgfX08L3NwYW4+PGlucHV0IHR5cGU9XCJmaWxlXCIgbmFtZT1cInt7IGlucHV0TmFtZSB9fVt7eyBmaWxlSWQgfX1dXCIgdmFsdWU9XCJcIi8+PGEgaHJlZj1cImphdmFzY3JpcHQ6O1wiIGNsYXNzPVwidWktZmlsZWF0dGFjaC1yZW1vdmUtYnRuXCI+PHNwYW4gY2xhc3M9XCJzci1vbmx5XCI+IFJlbW92ZTwvc3Bhbj48L2E+PC9sYWJlbD4nLFxuICAgICAgZXJyb3JNZXNzYWdlOiAnPGRpdiBjbGFzcz1cInVpLWZpbGVhdHRhY2gtZXJyb3JcIj48c3BhbiBjbGFzcz1cImMtZXJyb3JcIj57eyBlcnJvck1lc3NhZ2UgfX08L3NwYW4+IHt7IG1lc3NhZ2UgfX08L2Rpdj4gPHNwYW4gY2xhc3M9XCJ1aS1maWxlYXR0YWNoLWFkZC1idG5cIj5TZWxlY3QgZmlsZS4uLjwvc3Bhbj4nXG4gICAgfSxcblxuICAgIC8vIExhYmVsc1xuICAgIGxhbmc6IHtcbiAgICAgIGVtcHR5RmlsZTogJzxzcGFuIGNsYXNzPVwidWktZmlsZWF0dGFjaC1hZGQtYnRuXCI+U2VsZWN0IGZpbGUuLi48L3NwYW4+JyxcbiAgICAgIGVycm9yczoge1xuICAgICAgICBpbmNvcnJlY3RGaWxlVHlwZToge1xuICAgICAgICAgIGVycm9yTWVzc2FnZTogJ0ZpbGUgdHlwZSA8c3Ryb25nPnt7IGZpbGVUeXBlIH19PC9zdHJvbmc+IG5vdCBhY2NlcHRlZCcsXG4gICAgICAgICAgbWVzc2FnZTogJ0FjY2VwdGluZyBvbmx5OiA8c3Ryb25nPnt7IGFjY2VwdGVkRmlsZVR5cGVzIH19PC9zdHJvbmc+J1xuICAgICAgICB9LFxuICAgICAgICBpbmNvcnJlY3RGaWxlU2l6ZToge1xuICAgICAgICAgIGVycm9yTWVzc2FnZTogJ0ZpbGUgc2l6ZSBleGNlZWRzIG1heGltdW0gPHN0cm9uZz57eyBhY2NlcHRlZEZpbGVTaXplIH19PC9zdHJvbmc+JyxcbiAgICAgICAgICBtZXNzYWdlOiAnJ1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfSxcblxuICAgIC8vIEV2ZW50c1xuICAgIG9uYWRkOiBmdW5jdGlvbiAoKSB7fSxcbiAgICBvbnJlbW92ZTogZnVuY3Rpb24gKCkge31cbiAgfSxcbiAgLy8gLS0gT3B0aW9ucyAodmlhIGVsZW1lbnQgYXR0cmlidXRlcylcbiAgRWxlbWVudEF0dHJzT2JqZWN0KGVsZW0sIHtcbiAgICBtYXhGaWxlczogJ2RhdGEtZmlsZWF0dGFjaC1tYXhmaWxlcycsXG4gICAgbWF4U2l6ZTogJ2RhdGEtZmlsZWF0dGFjaC1tYXhzaXplJyxcbiAgICBmaWxlVHlwZXM6ICdkYXRhLWZpbGVhdHRhY2gtZmlsZXR5cGVzJyxcbiAgICBpbnB1dE5hbWU6ICdkYXRhLWZpbGVhdHRhY2gtaW5wdXRuYW1lJ1xuICB9KSxcbiAgLy8gLS0gT3B0aW9ucyAodmlhIEpTIG1ldGhvZCBjYWxsKVxuICBvcHRpb25zKVxuXG4gIC8vIEVsZW1lbnRzXG4gIHNlbGYuJGVsZW0uYWRkQ2xhc3MoJ3VpLWZpbGVhdHRhY2gnKVxuXG4gIC8vIEFkZCBhIGZpbGUgaXRlbSB0byB0aGUgbGlzdFxuICBzZWxmLmFkZCA9IGZ1bmN0aW9uIChpbmhpYml0UHJvbXB0KSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG4gICAgdmFyICRmaWxlcyA9IHNlbGYuZ2V0RmlsZXMoKVxuICAgIHZhciAkZW1wdHlJdGVtcyA9IHNlbGYuJGVsZW0uZmluZCgnLnVpLWZpbGVhdHRhY2gtaXRlbScpLm5vdCgnW2RhdGEtZmlsZWF0dGFjaC1pdGVtLXR5cGVdJylcblxuICAgIC8vIFNlZSBpZiBhbnkgYXJlIGVtcHR5IGFuZCBzZWxlY3QgdGhhdCBpbnN0ZWFkXG4gICAgaWYgKCRlbXB0eUl0ZW1zLmxlbmd0aCA+IDApIHtcbiAgICAgIGlmICghaW5oaWJpdFByb21wdCB8fCB0eXBlb2YgaW5oaWJpdFByb21wdCA9PT0gJ3VuZGVmaW5lZCcpICRlbXB0eUl0ZW1zLmZpcnN0KCkuY2xpY2soKVxuICAgICAgcmV0dXJuXG4gICAgfVxuXG4gICAgLy8gUHJldmVudCBtb3JlIGZpbGVzIGJlaW5nIGFkZGVkXG4gICAgaWYgKHNlbGYuc2V0dGluZ3MubWF4RmlsZXMgPiAwICYmICRmaWxlcy5sZW5ndGggPj0gc2VsZi5zZXR0aW5ncy5tYXhGaWxlcykgcmV0dXJuXG5cbiAgICAvLyBBcHBlbmQgYSBuZXcgZmlsZSBpbnB1dFxuICAgIHZhciAkZmlsZSA9ICQoVGVtcGxhdGluZy5yZXBsYWNlKHNlbGYuc2V0dGluZ3MudGVtcGxhdGVzLmZpbGVJdGVtLCB7XG4gICAgICBmaWxlTmFtZTogc2VsZi5zZXR0aW5ncy5sYW5nLmVtcHR5RmlsZSxcbiAgICAgIGlucHV0TmFtZTogc2VsZi5zZXR0aW5ncy5pbnB1dE5hbWUsXG4gICAgICBmaWxlSWQ6IHJhbmRvbVN0cmluZygpXG4gICAgfSkpXG4gICAgJGZpbGUuYXBwZW5kVG8oc2VsZi4kZWxlbSlcblxuICAgIC8vIEBkZWJ1Z1xuICAgIC8vIGNvbnNvbGUubG9nKCdhZGQgZm4nLCBpbmhpYml0UHJvbXB0LCAkZmlsZS5maW5kKCdbbmFtZV0nKS5maXJzdCgpLmF0dHIoJ25hbWUnKSlcblxuICAgIGlmICghaW5oaWJpdFByb21wdCB8fCB0eXBlb2YgaW5oaWJpdFByb21wdCA9PT0gJ3VuZGVmaW5lZCcpICRmaWxlLmNsaWNrKClcbiAgfVxuXG4gIC8vIEF0dGFjaCBhIGZpbGVcbiAgc2VsZi5hdHRhY2ggPSBmdW5jdGlvbiAoZmlsZUVsZW0pIHtcbiAgICB2YXIgc2VsZiA9IHRoaXNcbiAgICB2YXIgJGZpbGUgPSAkKGZpbGVFbGVtKVxuXG4gICAgLy8gR2V0IHRoZSBmaWxlIG5hbWVcbiAgICB2YXIgZmlsZU5hbWUgPSAkZmlsZS52YWwoKS5zcGxpdCgnXFxcXCcpLnBvcCgpXG4gICAgdmFyIGZpbGVUeXBlID0gJGZpbGUudmFsKCkuc3BsaXQoJy4nKS5wb3AoKS50b0xvd2VyQ2FzZSgpXG4gICAgdmFyIGZpbGVTaXplID0gMFxuXG4gICAgLy8gSFRNTDUgRmlsZXMgc3VwcG9ydFxuICAgIGlmICh0eXBlb2YgJGZpbGVbMF0uZmlsZXMgIT09ICd1bmRlZmluZWQnICYmICRmaWxlWzBdLmZpbGVzLmxlbmd0aCA+IDApIHtcbiAgICAgIGlmICh0eXBlb2YgJGZpbGVbMF0uZmlsZXNbMF0udHlwZSAhPT0gJ3VuZGVmaW5lZCcpIGZpbGVUeXBlID0gJGZpbGVbMF0uZmlsZXNbMF0udHlwZS5zcGxpdCgnLycpLnBvcCgpXG4gICAgICBpZiAodHlwZW9mICRmaWxlWzBdLmZpbGVzWzBdLnNpemUgIT09ICd1bmRlZmluZWQnKSBmaWxlU2l6ZSA9ICRmaWxlWzBdLmZpbGVzWzBdLnNpemVcbiAgICB9XG5cbiAgICAvLyBSZWplY3QgZmlsZSBiZWNhdXNlIG9mIHR5cGVcbiAgICBpZiAoc2VsZi5zZXR0aW5ncy5maWxlVHlwZXMgIT09ICcqJyAmJiAhKG5ldyBSZWdFeHAoJyAnICsgZmlsZVR5cGUgKyAnICcsICdpJykudGVzdCgnICcgKyBzZWxmLnNldHRpbmdzLmZpbGVUeXBlcyArICcgJykpKSB7XG4gICAgICAkZmlsZS52YWwoJycpXG4gICAgICAgIC5wYXJlbnRzKCcudWktZmlsZWF0dGFjaC1pdGVtJykucmVtb3ZlQXR0cignZGF0YS1maWxlYXR0YWNoLWl0ZW0tdHlwZScpXG4gICAgICAgIC5maW5kKCcubGFiZWwnKS5odG1sKFRlbXBsYXRpbmcucmVwbGFjZShzZWxmLnNldHRpbmdzLnRlbXBsYXRlcy5lcnJvck1lc3NhZ2UsIFtzZWxmLnNldHRpbmdzLmxhbmcuZXJyb3JzLmluY29ycmVjdEZpbGVUeXBlLCB7XG4gICAgICAgICAgZmlsZVR5cGU6IGZpbGVUeXBlLnRvVXBwZXJDYXNlKCksXG4gICAgICAgICAgYWNjZXB0ZWRGaWxlVHlwZXM6IHNlbGYuc2V0dGluZ3MuZmlsZVR5cGVzLnNwbGl0KC9bLCBdKy8pLmpvaW4oJywgJykudG9VcHBlckNhc2UoKVxuICAgICAgICB9XSkpXG4gICAgICByZXR1cm5cbiAgICB9XG5cbiAgICAvLyBSZWplY3QgZmlsZSBiZWNhdXNlIG9mIHNpemVcbiAgICBpZiAoc2VsZi5zZXR0aW5ncy5tYXhTaXplICYmIGZpbGVTaXplICYmIGZpbGVTaXplID4gc2VsZi5zZXR0aW5ncy5tYXhTaXplKSB7XG4gICAgICAkZmlsZS52YWwoJycpXG4gICAgICAgIC5wYXJlbnRzKCcudWktZmlsZWF0dGFjaC1pdGVtJykucmVtb3ZlQXR0cignZGF0YS1maWxlYXR0YWNoLWl0ZW0tdHlwZScpXG4gICAgICAgIC5maW5kKCcubGFiZWwnKS5odG1sKFRlbXBsYXRpbmcucmVwbGFjZShzZWxmLnNldHRpbmdzLnRlbXBsYXRlcy5lcnJvck1lc3NhZ2UsIFtzZWxmLnNldHRpbmdzLmxhbmcuZXJyb3JzLmluY29ycmVjdEZpbGVTaXplLCB7XG4gICAgICAgICAgZmlsZVNpemU6IGdldEZpbGVTaXplV2l0aFVuaXRzKGZpbGVTaXplKSxcbiAgICAgICAgICBhY2NlcHRlZEZpbGVTaXplOiBnZXRGaWxlU2l6ZVdpdGhVbml0cyhzZWxmLnNldHRpbmdzLm1heFNpemUpXG4gICAgICAgIH1dKSlcbiAgICAgIHJldHVyblxuICAgIH1cblxuICAgIC8vIEF0dGFjaCB0aGUgZmlsZVxuICAgICRmaWxlLnBhcmVudHMoJy51aS1maWxlYXR0YWNoLWl0ZW0nKS5hdHRyKHtcbiAgICAgIHRpdGxlOiBmaWxlTmFtZSxcbiAgICAgICdkYXRhLWZpbGVhdHRhY2gtaXRlbS10eXBlJzogZmlsZVR5cGVcbiAgICB9KS5maW5kKCcubGFiZWwnKS50ZXh0KGZpbGVOYW1lKVxuXG4gICAgLy8gQGRlYnVnXG4gICAgLy8gY29uc29sZS5sb2coJ2F0dGFjaCBmbicpXG5cbiAgICAvLyBJZiBjYW4gYWRkIGFub3RoZXIuLi5cbiAgICBpZiAoc2VsZi5nZXRGaWxlcygpLmxlbmd0aCA8IHNlbGYuc2V0dGluZ3MubWF4RmlsZXMpIHNlbGYuYWRkKHRydWUpXG4gIH1cblxuICAvLyBSZW1vdmUgYSBmaWxlXG4gIHNlbGYucmVtb3ZlID0gZnVuY3Rpb24gKGZpbGVJbmRleCkge1xuICAgIHZhciBzZWxmID0gdGhpc1xuICAgIGlmICh0eXBlb2YgZmlsZUluZGV4ID09PSAndW5kZWZpbmVkJykgcmV0dXJuXG5cbiAgICAvLyBHZXQgdGhlIGZpbGUgYW5kIGxhYmVsIGVsZW1lbnRzXG4gICAgaWYgKHNlbGYuZ2V0RmlsZXMoKS5sZW5ndGggPiAxKSB7XG4gICAgICBzZWxmLmdldEZpbGUoZmlsZUluZGV4KS5wYXJlbnRzKCdsYWJlbCcpLnJlbW92ZSgpXG4gICAgfSBlbHNlIHtcbiAgICAgIHNlbGYuZ2V0RmlsZXMoKS52YWwoJycpXG4gICAgICAgIC5wYXJlbnRzKCcudWktZmlsZWF0dGFjaC1pdGVtJykucmVtb3ZlQXR0cignZGF0YS1maWxlYXR0YWNoLWl0ZW0tdHlwZScpXG4gICAgICAgIC5maW5kKCcubGFiZWwnKS5odG1sKHNlbGYuc2V0dGluZ3MubGFuZy5lbXB0eUZpbGUpXG4gICAgfVxuXG4gICAgLy8gQGRlYnVnXG4gICAgLy8gY29uc29sZS5sb2coJ3JlbW92ZSBmbicpXG5cbiAgICAvLyBJZiBjYW4gYWRkIGFub3RoZXIuLi5cbiAgICBpZiAoc2VsZi5nZXRGaWxlcygpLmxlbmd0aCA8IHNlbGYuc2V0dGluZ3MubWF4RmlsZXMpIHNlbGYuYWRkKHRydWUpXG5cbiAgICBzZWxmLiRlbGVtLnRyaWdnZXIoJ0ZpbGVBdHRhY2g6cmVtb3ZlZCcsIFtzZWxmLCBmaWxlSW5kZXhdKVxuICB9XG5cbiAgLy8gQ2xlYXIgYWxsIGZpbGVzXG4gIHNlbGYuY2xlYXIgPSBmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgICAvLyBDbGVhciB0aGUgbGlzdCBvZiBmaWxlc1xuICAgIHNlbGYuZ2V0RmlsZXMoKS5lYWNoKGZ1bmN0aW9uIChpLCBmaWxlKSB7XG4gICAgICAkKGZpbGUpLnBhcmVudHMoJy51aS1maWxlYXR0YWNoLWl0ZW0nKS5yZW1vdmUoKVxuICAgIH0pXG5cbiAgICAvLyBBZGQgbmV3IGZpbGVcbiAgICBzZWxmLmFkZCh0cnVlKVxuXG4gICAgc2VsZi4kZWxlbS50cmlnZ2VyKCdGaWxlQXR0YWNoZWQ6Y2xlYXJlZCcsIFtzZWxmXSlcbiAgfVxuXG4gIC8vIEdldCBmaWxlIGVsZW1lbnRzXG4gIHNlbGYuZ2V0RmlsZXMgPSBmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG4gICAgcmV0dXJuIHNlbGYuJGVsZW0uZmluZCgnLnVpLWZpbGVhdHRhY2gtaXRlbSBpbnB1dFt0eXBlPVwiZmlsZVwiXScpXG4gIH1cblxuICAvLyBHZXQgc2luZ2xlIGZpbGUgZWxlbWVudFxuICBzZWxmLmdldEZpbGUgPSBmdW5jdGlvbiAoZmlsZUluZGV4KSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG4gICAgcmV0dXJuIHNlbGYuZ2V0RmlsZXMoKS5lcShmaWxlSW5kZXgpXG4gIH1cblxuICAvLyBJbml0XG4gIGlmIChzZWxmLmdldEZpbGVzKCkubGVuZ3RoID09PSAwKSB7XG4gICAgc2VsZi5hZGQodHJ1ZSlcbiAgfVxuICBzZWxmLiRlbGVtWzBdLkZpbGVBdHRhY2ggPSBzZWxmXG59XG5cbi8vIEV2ZW50c1xuJChkb2N1bWVudClcbiAgLy8gLS0gQ2xpY2sgYWRkIGJ1dHRvblxuICAub24oJ2NsaWNrJywgJy51aS1maWxlYXR0YWNoLWFkZC1idG4nLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpXG4gICAgJCh0aGlzKS5wYXJlbnRzKCcudWktZmlsZWF0dGFjaCcpLnVpRmlsZUF0dGFjaCgnYWRkJylcbiAgfSlcblxuICAvLyAtLSBDbGljayByZW1vdmUgYnV0dG9uXG4gIC5vbignY2xpY2snLCAnLnVpLWZpbGVhdHRhY2gtcmVtb3ZlLWJ0bicsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICBldmVudC5zdG9wUHJvcGFnYXRpb24oKVxuXG4gICAgLy8gQGRlYnVnXG4gICAgLy8gY29uc29sZS5sb2coJ2NsaWNrIHJlbW92ZSBidG4nKVxuXG4gICAgdmFyICRmYSA9ICQodGhpcykucGFyZW50cygnLnVpLWZpbGVhdHRhY2gnKVxuICAgIHZhciBmYSA9ICRmYVswXS5GaWxlQXR0YWNoXG4gICAgdmFyICRmaWxlID0gJCh0aGlzKS5wYXJlbnRzKCcudWktZmlsZWF0dGFjaC1pdGVtJykuZmluZCgnaW5wdXRbdHlwZT1cImZpbGVcIl0nKVxuICAgIHZhciBmaWxlSW5kZXggPSBmYS5nZXRGaWxlcygpLmluZGV4KCRmaWxlKVxuICAgICRmYS51aUZpbGVBdHRhY2goJ3JlbW92ZScsIGZpbGVJbmRleClcbiAgfSlcblxuICAvLyAtLSBXaGVuIHRoZSBmaWxlIGlucHV0IGhhcyBjaGFuZ2VkXG4gIC5vbignY2hhbmdlJywgJy51aS1maWxlYXR0YWNoIGlucHV0W3R5cGU9XCJmaWxlXCJdJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgaWYgKCQodGhpcykudmFsKCkpICQodGhpcykucGFyZW50cygnLnVpLWZpbGVhdHRhY2gnKS51aUZpbGVBdHRhY2goJ2F0dGFjaCcsIHRoaXMpXG4gIH0pXG5cbiAgLy8gLS0gRG9jdW1lbnQgcmVhZHk6IGF1dG8tYXBwbHkgZnVuY3Rpb25hbGl0eSB0byBlbGVtZW50cyB3aXRoIFtkYXRhLWZpbGVhdHRhY2hdIGF0dHJpYnV0ZVxuICAub24oJ3JlYWR5JywgZnVuY3Rpb24gKCkge1xuICAgICQoJ1tkYXRhLWZpbGVhdHRhY2hdJykudWlGaWxlQXR0YWNoKClcbiAgfSlcblxuJC5mbi51aUZpbGVBdHRhY2ggPSBmdW5jdGlvbiAob3ApIHtcbiAgLy8gRmlyZSBhIGNvbW1hbmQgdG8gdGhlIEZpbGVBdHRhY2ggb2JqZWN0LCBlLmcuICQoJ1tkYXRhLWZpbGVhdHRhY2hdJykudWlGaWxlQXR0YWNoKCdhZGQnLCB7Li59KVxuICBpZiAodHlwZW9mIG9wID09PSAnc3RyaW5nJyAmJiAvXmFkZHxhdHRhY2h8cmVtb3ZlfGNsZWFyJC8udGVzdChvcCkpIHtcbiAgICAvLyBHZXQgZnVydGhlciBhZGRpdGlvbmFsIGFyZ3VtZW50cyB0byBhcHBseSB0byB0aGUgbWF0Y2hlZCBjb21tYW5kIG1ldGhvZFxuICAgIHZhciBhcmdzID0gQXJyYXkucHJvdG90eXBlLnNsaWNlLmNhbGwoYXJndW1lbnRzKVxuICAgIGFyZ3Muc2hpZnQoKVxuXG4gICAgLy8gRmlyZSBjb21tYW5kIG9uIGVhY2ggcmV0dXJuZWQgZWxlbSBpbnN0YW5jZVxuICAgIHJldHVybiB0aGlzLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICAgIGlmIChlbGVtLkZpbGVBdHRhY2ggJiYgdHlwZW9mIGVsZW0uRmlsZUF0dGFjaFtvcF0gPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgZWxlbS5GaWxlQXR0YWNoW29wXS5hcHBseShlbGVtLkZpbGVBdHRhY2gsIGFyZ3MpXG4gICAgICB9XG4gICAgfSlcblxuICAvLyBTZXQgdXAgYSBuZXcgRmlsZUF0dGFjaCBpbnN0YW5jZSBwZXIgZWxlbSAoaWYgb25lIGRvZXNuJ3QgYWxyZWFkeSBleGlzdClcbiAgfSBlbHNlIHtcbiAgICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgICBpZiAoIWVsZW0uRmlsZUF0dGFjaCkge1xuICAgICAgICBuZXcgRmlsZUF0dGFjaChlbGVtLCBvcClcbiAgICAgIH1cbiAgICB9KVxuICB9XG59XG5cbm1vZHVsZS5leHBvcnRzID0gRmlsZUF0dGFjaFxuIiwiLypcbiAqIFVuaWxlbmQgRm9ybSBWYWxpZGF0aW9uXG4gKi9cblxuLy8gQFRPRE8gRGljdGlvbmFyeSBpbnRlZ3JhdGlvblxuXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbClcbnZhciBzcHJpbnRmID0gcmVxdWlyZSgnc3ByaW50Zi1qcycpLnNwcmludGZcbnZhciBJYmFuID0gcmVxdWlyZSgnaWJhbicpXG52YXIgRWxlbWVudEF0dHJzT2JqZWN0ID0gcmVxdWlyZSgnRWxlbWVudEF0dHJzT2JqZWN0JylcbnZhciBUZW1wbGF0aW5nID0gcmVxdWlyZSgnVGVtcGxhdGluZycpXG52YXIgRGljdGlvbmFyeSA9IHJlcXVpcmUoJ0RpY3Rpb25hcnknKVxudmFyIF9fID0gbmV3IERpY3Rpb25hcnkoe1xuICBcImVuXCI6IHtcbiAgICBcImVycm9yRmllbGRSZXF1aXJlZFwiOiBcIkZpZWxkIGNhbm5vdCBiZSBlbXB0eVwiLFxuICAgIFwiZXJyb3JGaWVsZFJlcXVpcmVkQ2hlY2tib3hcIjogXCJQbGVhc2UgY2hlY2sgdGhlIGJveCB0byBjb250aW51ZVwiLFxuICAgIFwiZXJyb3JGaWVsZFJlcXVpcmVkQ2hlY2tib3hlc1wiOiBcIlBsZWFzZSBzZWxlY3QgYW4gb3B0aW9uIHRvIGNvbnRpbnVlXCIsXG4gICAgXCJlcnJvckZpZWxkUmVxdWlyZWRSYWRpb1wiOiBcIlBsZWFzZSBzZWxlY3QgYW4gb3B0aW9uIHRvIGNvbnRpbnVlXCIsXG4gICAgXCJlcnJvckZpZWxkUmVxdWlyZWRTZWxlY3RcIjogXCJQbGVhc2Ugc2VsZWN0IGFuIG9wdGlvbiB0byBjb250aW51ZVwiLFxuICAgIFwiZXJyb3JGaWVsZE1pbkxlbmd0aFwiOiBcIlBsZWFzZSBlbnN1cmUgZmllbGQgaXMgYXQgbGVhc3QgJWQgY2hhcmFjdGVycyBsb25nXCIsXG4gICAgXCJlcnJvckZpZWxkTWF4TGVuZ3RoXCI6IFwiUGxlYXNlIGVuc3VyZSBmaWVsZCBkb2VzIG5vdCBleGNlZWQgJWQgY2hhcmFjdGVyc1wiLFxuICAgIFwiZXJyb3JmaWVsZElucHV0VHlwZU51bWJlclwiOiBcIk5vdCBhIHZhbGlkIG51bWJlclwiLFxuICAgIFwiZXJyb3JmaWVsZElucHV0VHlwZUVtYWlsXCI6IFwiTm90IGEgdmFsaWQgZW1haWwgYWRkcmVzc1wiLFxuICAgIFwiZXJyb3JmaWVsZElucHV0VHlwZVRlbGVwaG9uZVwiOiBcIk5vdCBhIHZhbGlkIGVtYWlsIHRlbGVwaG9uZSBudW1iZXJcIlxuICB9XG59LCAnZW4nKVxuXG5mdW5jdGlvbiBnZXRMYWJlbEZvckVsZW0gKGVsZW0pIHtcbiAgdmFyICRlbGVtID0gJChlbGVtKVxuICB2YXIgbGFiZWwgPSAnJ1xuICB2YXIgbGFiZWxsZWRCeSA9ICRlbGVtLmF0dHIoJ2FyaWEtbGFiZWxsZWRieScpXG4gIHZhciAkbGFiZWwgPSAkKCdsYWJlbFtmb3I9XCInICsgJGVsZW0uYXR0cignaWQnKSArICdcIl0nKS5maXJzdCgpXG5cbiAgLy8gTGFiZWxsZWQgYnkgb3RoZXIgZWxlbWVudHNcbiAgaWYgKGxhYmVsbGVkQnkpIHtcbiAgICBsYWJlbCA9IFtdXG5cbiAgICAvLyBHZXQgZWxlbWVudHMgdGhhdCBlbGVtZW50IGhhcyBiZWVuIGxhYmVsbGVkIGJ5XG4gICAgaWYgKC8gLy50ZXN0KGxhYmVsbGVkQnkpKSB7XG4gICAgICBsYWJlbGxlZEJ5ID0gbGFiZWxsZWRCeS5zcGxpdCgnICcpXG4gICAgfSBlbHNlIHtcbiAgICAgIGxhYmVsbGVkQnkgPSBbbGFiZWxsZWRCeV1cbiAgICB9XG5cbiAgICAkLmVhY2gobGFiZWxsZWRCeSwgZnVuY3Rpb24gKGksIGxhYmVsKSB7XG4gICAgICB2YXIgJGxhYmVsbGVkQnkgPSAkKCcjJyArIGxhYmVsKS5maXJzdCgpXG4gICAgICBpZiAoJGxhYmVsbGVkQnkubGVuZ3RoID4gMCkge1xuICAgICAgICBsYWJlbC5wdXNoKCRsYWJlbGxlZEJ5LnRleHQoKSlcbiAgICAgIH1cbiAgICB9KVxuXG4gICAgLy8gTGFiZWxzIGdvIGluIHJldmVyc2Ugb3JkZXI/XG4gICAgbGFiZWwgPSBsYWJlbC5yZXZlcnNlKCkuam9pbignICcpXG5cbiAgLy8gTGFiZWxsZWQgYnkgdHJhZGl0aW9uYWwgbWV0aG9kLCBlLmcuIGxhYmVsW2Zvcj1cImlkLW9mLWVsZW1lbnRcIl1cbiAgfSBlbHNlIHtcbiAgICBpZiAoJGxhYmVsLmxlbmd0aCA+IDApIHtcbiAgICAgIGxhYmVsID0gJGxhYmVsLnRleHQoKVxuXG4gICAgICAvLyBMYWJlbCBsYWJlbFxuICAgICAgaWYgKCRsYWJlbC5maW5kKCcubGFiZWwnKS5sZW5ndGggPiAwKSB7XG4gICAgICAgIGxhYmVsID0gJGxhYmVsLmZpbmQoJy5sYWJlbCcpLnRleHQoKVxuICAgICAgfVxuICAgIH1cbiAgfVxuXG4gIHJldHVybiBsYWJlbC5yZXBsYWNlKC9cXHMrL2csICcgJykudHJpbSgpXG59XG5cbi8vIEdldCB0aGUgZmllbGQncyB2YWx1ZVxuZnVuY3Rpb24gZ2V0RmllbGRWYWx1ZSAoZWxlbSkge1xuICB2YXIgJGVsZW0gPSAkKGVsZW0pXG4gIHZhciB2YWx1ZSA9IHVuZGVmaW5lZFxuXG4gIC8vIE5vIGVsZW1cbiAgaWYgKCRlbGVtLmxlbmd0aCA9PT0gMCkgcmV0dXJuIHZhbHVlXG5cbiAgLy8gRWxlbSBpcyBhIHNpbmdsZSBpbnB1dFxuICBpZiAoJGVsZW0uaXMoJ2lucHV0LCB0ZXh0YXJlYSwgc2VsZWN0JykpIHtcbiAgICBpZiAoJGVsZW0uaXMoJ1t0eXBlPVwicmFkaW9cIl0sIFt0eXBlPVwiY2hlY2tib3hcIl0nKSkge1xuICAgICAgaWYgKCRlbGVtLmlzKCc6Y2hlY2tlZCwgOnNlbGVjdGVkJykpIHtcbiAgICAgICAgdmFsdWUgPSAkZWxlbS52YWwoKVxuICAgICAgfVxuICAgIH0gZWxzZSB7XG4gICAgICB2YWx1ZSA9ICRlbGVtLnZhbCgpXG4gICAgfVxuXG4gIC8vIEVsZW0gY29udGFpbnMgbXVsdGlwbGUgaW5wdXRzXG4gIH0gZWxzZSB7XG4gICAgLy8gU2VhcmNoIGluc2lkZSBmb3IgaW5wdXRzXG4gICAgdmFyICRpbnB1dHMgPSAkZWxlbS5maW5kKCdpbnB1dCwgdGV4dGFyZWEsIHNlbGVjdCcpXG5cbiAgICAvLyBObyBpbnB1dHNcbiAgICBpZiAoJGlucHV0cy5sZW5ndGggPT09IDApIHJldHVybiB2YWx1ZVxuXG4gICAgLy8gR2V0IGlucHV0IHZhbHVlc1xuICAgIHZhciBpbnB1dE5hbWVzID0gW11cbiAgICB2YXIgaW5wdXRWYWx1ZXMgPSB7fVxuICAgICRpbnB1dHMuZWFjaChmdW5jdGlvbiAoaSwgaW5wdXQpIHtcbiAgICAgIHZhciAkaW5wdXQgPSAkKGlucHV0KVxuICAgICAgdmFyIGlucHV0TmFtZSA9ICRpbnB1dC5hdHRyKCduYW1lJylcbiAgICAgIHZhciBpbnB1dFZhbHVlID0gZ2V0RmllbGRWYWx1ZShpbnB1dClcblxuICAgICAgaWYgKHR5cGVvZiBpbnB1dFZhbHVlICE9PSAndW5kZWZpbmVkJykge1xuICAgICAgICBpZiAoJC5pbkFycmF5KGlucHV0TmFtZSwgaW5wdXROYW1lcykgPT09IC0xKSB7XG4gICAgICAgICAgaW5wdXROYW1lcy5wdXNoKGlucHV0TmFtZSlcbiAgICAgICAgfVxuICAgICAgICBpbnB1dFZhbHVlc1tpbnB1dE5hbWVdID0gJGlucHV0LnZhbCgpXG4gICAgICB9XG4gICAgfSlcblxuICAgIC8vIEBkZWJ1Z1xuICAgIC8vIGNvbnNvbGUubG9nKCdnZXRGaWVsZFZhbHVlOmdyb3VwZWRpbnB1dHMnLCB7XG4gICAgLy8gICAkaW5wdXRzOiAkaW5wdXRzLFxuICAgIC8vICAgaW5wdXROYW1lczogaW5wdXROYW1lcyxcbiAgICAvLyAgIGlucHV0VmFsdWVzOiBpbnB1dFZhbHVlc1xuICAgIC8vIH0pXG5cbiAgICAvLyBUaGUgcmV0dXJuIHZhbHVlXG4gICAgaWYgKGlucHV0TmFtZXMubGVuZ3RoID09PSAxKSB7XG4gICAgICB2YWx1ZSA9IGlucHV0VmFsdWVzW2lucHV0TmFtZXNbMF1dXG4gICAgfSBlbHNlIGlmIChpbnB1dE5hbWVzLmxlbmd0aCA+IDEpIHtcbiAgICAgIHZhbHVlID0gaW5wdXRWYWx1ZXNcbiAgICB9XG4gIH1cblxuICAvLyBAZGVidWdcbiAgLy8gY29uc29sZS5sb2coJ2dldEZpZWxkVmFsdWUnLCB2YWx1ZSlcblxuICByZXR1cm4gdmFsdWVcbn1cblxuLy8gR2V0IHRoZSBmaWVsZCdzIGlucHV0IHR5cGVcbmZ1bmN0aW9uIGdldEZpZWxkVHlwZSAoZWxlbSkge1xuICB2YXIgJGVsZW0gPSAkKGVsZW0pXG4gIHZhciB0eXBlID0gdW5kZWZpbmVkXG5cbiAgLy8gRXJyb3JcbiAgaWYgKCRlbGVtLmxlbmd0aCA9PT0gMCkgcmV0dXJuIHVuZGVmaW5lZFxuXG4gIC8vIFNpbmdsZSBpbnB1dHNcbiAgaWYgKCRlbGVtLmlzKCdpbnB1dCcpKSB7XG4gICAgdHlwZSA9ICRlbGVtLmF0dHIoJ3R5cGUnKVxuXG4gIH0gZWxzZSBpZiAoJGVsZW0uaXMoJ3RleHRhcmVhJykpIHtcbiAgICB0eXBlID0gJ3RleHQnXG5cbiAgfSBlbHNlIGlmICgkZWxlbS5pcygnc2VsZWN0JykpIHtcbiAgICB0eXBlID0gJ3NlbGVjdCdcblxuICAvLyBHcm91cGVkIGlucHV0c1xuICB9IGVsc2UgaWYgKCEkZWxlbS5pcygnaW5wdXQsIHNlbGVjdCwgdGV4dGFyZWEnKSkge1xuICAgIC8vIEdldCBhbGwgdGhlIHZhcmlvdXMgaW5wdXQgdHlwZXMgd2l0aGluIHRoaXMgZWxlbWVudFxuICAgIHZhciAkaW5wdXRzID0gJGVsZW0uZmluZCgnaW5wdXQsIHNlbGVjdCwgdGV4dGFyZWEnKVxuICAgIGlmICgkaW5wdXRzLmxlbmd0aCA+IDApIHtcbiAgICAgIHZhciBpbnB1dFR5cGVzID0gW11cbiAgICAgICRpbnB1dHMuZWFjaChmdW5jdGlvbiAoaSwgaW5wdXQpIHtcbiAgICAgICAgdmFyIGlucHV0VHlwZSA9IGdldEZpZWxkVHlwZShpbnB1dClcbiAgICAgICAgaWYgKGlucHV0VHlwZSAmJiAkLmluQXJyYXkoaW5wdXRUeXBlLCBpbnB1dFR5cGVzKSA9PT0gLTEpIGlucHV0VHlwZXMucHVzaChpbnB1dFR5cGUpXG4gICAgICB9KVxuXG4gICAgICAvLyBQdXQgaW50byBzdHJpbmcgdG8gcmV0dXJuXG4gICAgICBpZiAoJGlucHV0cy5sZW5ndGggPiAxKSBpbnB1dFR5cGVzLnVuc2hpZnQoJ211bHRpJylcbiAgICAgIGlmIChpbnB1dFR5cGVzLmxlbmd0aCA+IDApIHR5cGUgPSBpbnB1dFR5cGVzLmpvaW4oJyAnKVxuXG4gICAgICAvLyBAZGVidWdcbiAgICAgIC8vIGNvbnNvbGUubG9nKCdnZXRGaWVsZFR5cGU6bm9uX2lucHV0Jywge1xuICAgICAgLy8gICBpbnB1dFR5cGVzOiBpbnB1dFR5cGVzLFxuICAgICAgLy8gICAkaW5wdXRzOiAkaW5wdXRzXG4gICAgICAvLyB9KVxuICAgIH1cbiAgfVxuXG4gIC8vIEBkZWJ1Z1xuICAvLyBjb25zb2xlLmxvZygnZ2V0RmllbGRUeXBlJywge1xuICAvLyAgIGVsZW06IGVsZW0sXG4gIC8vICAgdHlwZTogdHlwZVxuICAvLyB9KVxuXG4gIHJldHVybiB0eXBlXG59XG5cbnZhciBGb3JtVmFsaWRhdGlvbiA9IGZ1bmN0aW9uIChlbGVtLCBvcHRpb25zKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICBzZWxmLiRlbGVtID0gJChlbGVtKVxuXG4gIC8vIEVycm9yXG4gIGlmIChzZWxmLiRlbGVtLmxlbmd0aCA9PT0gMCkgcmV0dXJuXG5cbiAgLy8gU2V0dGluZ3NcbiAgc2VsZi5zZXR0aW5ncyA9ICQuZXh0ZW5kKHtcbiAgICAvLyBUaGUgZm9ybVxuICAgIGZvcm1FbGVtOiBmYWxzZSxcblxuICAgIC8vIEFuIGVsZW1lbnQgdGhhdCBjb250YWlucyBub3RpZmljYXRpb25zIChpLmUuIG1lc3NhZ2VzIHRvIHNlbmQgdG8gdGhlIHVzZXIpXG4gICAgbm90aWZpY2F0aW9uc0VsZW06IGZhbHNlLFxuXG4gICAgLy8gV2hldGhlciB0byB2YWxpZGF0ZSBvbiB0aGUgZm9ybSBvciBvbiB0aGUgaW5kaXZpZHVhbCBmaWVsZCBldmVudFxuICAgIHZhbGlkYXRlT25Gb3JtRXZlbnRzOiB0cnVlLFxuICAgIHZhbGlkYXRlT25GaWVsZEV2ZW50czogdHJ1ZSxcblxuICAgIC8vIFRoZSBzcGVjaWZpYyBldmVudHMgdG8gd2F0Y2ggdG8gdHJpZ2dlciB0aGUgZm9ybS9maWVsZCB2YWxpZGF0aW9uXG4gICAgd2F0Y2hGb3JtRXZlbnRzOiAnc3VibWl0JyxcbiAgICB3YXRjaEZpZWxkRXZlbnRzOiAna2V5ZG93biBibHVyIGNoYW5nZScsXG5cbiAgICAvLyBTaG93IHN1Y2Nlc3NmdWwvZXJyb3JlZCB2YWxpZGF0aW9uIG9uIGZpZWxkXG4gICAgc2hvd1N1Y2Nlc3NPbkZpZWxkOiB0cnVlLFxuICAgIHNob3dFcnJvck9uRmllbGQ6IHRydWUsXG4gICAgc2hvd0FsbEVycm9yczogZmFsc2UsXG5cbiAgICAvLyBVcGRhdGUgdGhlIHZpZXcgKGRpc2FibGUgaWYgeW91IGhhdmUgeW91ciBvd24gcmVuZGVyaW5nIGNhbGxiYWNrcylcbiAgICByZW5kZXI6IHRydWUsXG5cbiAgICAvLyBUaGUgY2FsbGJhY2sgdG8gZmlyZSBiZWZvcmUgdmFsaWRhdGluZyBhIGZpZWxkXG4gICAgb25maWVsZGJlZm9yZXZhbGlkYXRlOiBmdW5jdGlvbiAoKSB7fSxcblxuICAgIC8vIFRoZSBjYWxsYmFjayB0byBmaXJlIGFmdGVyIHZhbGlkYXRpbmcgYSBmaWVsZFxuICAgIG9uZmllbGRhZnRlcnZhbGlkYXRlOiBmdW5jdGlvbiAoKSB7fSxcblxuICAgIC8vIFRoZSBjYWxsYmFjayB0byBmaXJlIHdoZW4gYSBmaWVsZCBwYXNzZWQgdmFsaWRhdGlvbiBzdWNjZXNzZnVsbHlcbiAgICBvbmZpZWxkc3VjY2VzczogZnVuY3Rpb24gKCkge30sXG5cbiAgICAvLyBUaGUgY2FsbGJhY2sgdG8gZmlyZSB3aGVuIGEgZmllbGQgZGlkIG5vdCBwYXNzIHZhbGlkYXRpb25cbiAgICBvbmZpZWxkZXJyb3I6IGZ1bmN0aW9uICgpIHt9LFxuXG4gICAgLy8gVGhlIGNhbGxiYWNrIHRvIGZpcmUgYmVmb3JlIGZvcm0vZ3JvdXAgdmFsaWRhdGlvblxuICAgIG9uYmVmb3JldmFsaWRhdGU6IGZ1bmN0aW9uICgpIHt9LFxuXG4gICAgLy8gVGhlIGNhbGxiYWNrIHRvIGZpcmUgYWZ0ZXIgZm9ybS9ncm91cCB2YWxpZGF0aW9uXG4gICAgb25hZnRlcnZhbGlkYXRlOiBmdW5jdGlvbiAoKSB7fSxcblxuICAgIC8vIFRoZSBjYWxsYmFjayB0byBmaXJlIHdoZW4gdGhlIGZvcm0vZ3JvdXAgcGFzc2VkIHZhbGlkYXRpb24gc3VjY2Vzc2Z1bGx5XG4gICAgb25zdWNjZXNzOiBmdW5jdGlvbiAoKSB7fSxcblxuICAgIC8vIFRoZSBjYWxsYmFjayB0byBmaXJlIHdoZW4gdGhlIGZvcm0vZ3JvdXAgZGlkIG5vdCBwYXNzIHZhbGlkYXRpb25cbiAgICBvbmVycm9yOiBmdW5jdGlvbiAoKSB7fSxcblxuICAgIC8vIFRoZSBjYWxsYmFjayB0byBmaXJlIHdoZW4gdGhlIGZvcm0vZ3JvdXAgY29tcGxldGVkIHZhbGlkYXRpb25cbiAgICBvbmNvbXBsZXRlOiBmdW5jdGlvbiAoKSB7fVxuICB9LCBFbGVtZW50QXR0cnNPYmplY3QoZWxlbSwge1xuICAgIGZvcm1FbGVtOiAnZGF0YS1mb3JtdmFsaWRhdGlvbi1mb3JtZWxlbScsXG4gICAgbm90aWZpY2F0aW9uc0VsZW06ICdkYXRhLWZvcm12YWxpZGF0aW9uLW5vdGlmaWNhdGlvbnNlbGVtJyxcbiAgICByZW5kZXI6ICdkYXRhLWZvcm12YWxpZGF0aW9uLXJlbmRlcidcbiAgfSksIG9wdGlvbnMpXG5cbiAgLy8gUHJvcGVydGllc1xuICAvLyAtLSBHZXQgdGhlIHRhcmdldCBmb3JtIHRoYXQgdGhpcyB2YWxpZGF0aW9uIGNvbXBvbmVudCBpcyByZWZlcmVuY2luZ1xuICBpZiAoc2VsZi4kZWxlbS5pcygnZm9ybScpKSBzZWxmLiRmb3JtID0gc2VsZi4kZWxlbVxuICBpZiAoc2VsZi5zZXR0aW5ncy5mb3JtRWxlbSkgc2VsZi4kZm9ybSA9ICQoc2VsZi5zZXR0aW5ncy5mb3JtRWxlbSlcbiAgaWYgKCFzZWxmLiRmb3JtIHx8IHNlbGYuJGZvcm0ubGVuZ3RoID09PSAwKSBzZWxmLiRmb3JtID0gc2VsZi4kZWxlbS5wYXJlbnRzKCdmb3JtJykuZmlyc3QoKVxuICAvLyAtLSBHZXQgdGhlIG5vdGlmaWNhdGlvbnMgZWxlbWVudFxuICBzZWxmLiRub3RpZmljYXRpb25zID0gJChzZWxmLnNldHRpbmdzLm5vdGlmaWNhdGlvbnNFbGVtKVxuICBpZiAoc2VsZi4kbm90aWZpY2F0aW9ucy5sZW5ndGggPiAwICYmICEkLmNvbnRhaW5zKGRvY3VtZW50LCBzZWxmLiRub3RpZmljYXRpb25zWzBdKSkgc2VsZi4kZm9ybS5wcmVwZW5kKHNlbGYuJG5vdGlmaWNhdGlvbnMpXG5cbiAgLy8gQGRlYnVnXG4gIC8vIGNvbnNvbGUubG9nKHtcbiAgLy8gICAkZWxlbTogc2VsZi4kZWxlbSxcbiAgLy8gICAkZm9ybTogc2VsZi4kZm9ybSxcbiAgLy8gICBzZXR0aW5nczogc2VsZi5zZXR0aW5nc1xuICAvLyB9KVxuXG4gIC8vIFNldHVwIFVJXG4gIHNlbGYuJGVsZW0uYWRkQ2xhc3MoJ3VpLWZvcm12YWxpZGF0aW9uJylcblxuICAvKlxuICAgKiBNZXRob2RzXG4gICAqL1xuICAvLyBWYWxpZGF0ZSBhIHNpbmdsZSBmb3JtIGZpZWxkXG4gIHNlbGYudmFsaWRhdGVGaWVsZCA9IGZ1bmN0aW9uIChlbGVtLCBvcHRpb25zKSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG4gICAgdmFyICRlbGVtID0gJChlbGVtKS5maXJzdCgpXG5cbiAgICAvLyBFcnJvclxuICAgIGlmICgkZWxlbS5sZW5ndGggPT09IDApIHJldHVybiBmYWxzZVxuXG4gICAgLy8gRmllbGQgaXMgZ3JvdXAgb2YgcmVsYXRlZCBpbnB1dHM6IGNoZWNrYm94LCByYWRpb1xuICAgIGlmICghJGVsZW0uaXMoJ2lucHV0LCB0ZXh0YXJlYSwgc2VsZWN0JykpIHtcbiAgICAgIC8vIEBkZWJ1Z1xuICAgICAgLy8gY29uc29sZS5sb2coJ0Zvcm1WYWxpZGF0aW9uLnZhbGlkYXRlRmllbGQgRXJyb3I6IG1ha2Ugc3VyZSB0aGUgZWxlbSBpc+KAlG9yIGNvbnRhaW5z4oCUYSBpbnB1dCwgdGV4dGFyZWEgb3Igc2VsZWN0JylcbiAgICAgIGlmICgkZWxlbS5maW5kKCdpbnB1dCwgdGV4dGFyZWEsIHNlbGVjdCcpLmxlbmd0aCA9PT0gMCkgcmV0dXJuIGZhbHNlXG4gICAgfVxuXG4gICAgLy8gSWdub3JlIGRpc2FibGVkIGZpZWxkc1xuICAgIGlmICgkZWxlbS5pcygnOmRpc2FibGVkJykpIHJldHVybiBmYWxzZVxuXG4gICAgLy8gRmllbGQgdmFsaWRhdGlvbiBvYmplY3RcbiAgICB2YXIgZmllbGRWYWxpZGF0aW9uID0ge1xuICAgICAgaXNWYWxpZDogZmFsc2UsXG4gICAgICAkZWxlbTogJGVsZW0sXG4gICAgICAkZm9ybUZpZWxkOiAkZWxlbS5wYXJlbnRzKCcuZm9ybS1maWVsZCcpLmZpcnN0KCksXG4gICAgICB2YWx1ZTogZ2V0RmllbGRWYWx1ZSgkZWxlbSksXG4gICAgICB0eXBlOiAnYXV0bycsIC8vIFNldCB0byBhdXRvLWRldGVjdCB0eXBlXG4gICAgICBlcnJvcnM6IFtdLFxuICAgICAgbWVzc2FnZXM6IFtdLFxuICAgICAgb3B0aW9uczogJC5leHRlbmQoe1xuICAgICAgICAvLyBSdWxlcyB0byB2YWxpZGF0ZSB0aGlzIGZpZWxkIGJ5XG4gICAgICAgIHJ1bGVzOiAkLmV4dGVuZCh7fSwgc2VsZi5ydWxlcy5kZWZhdWx0UnVsZXMsXG4gICAgICAgIC8vIEluaGVyaXQgYXR0cmlidXRlIHZhbHVlc1xuICAgICAgICBFbGVtZW50QXR0cnNPYmplY3QoZWxlbSwge1xuICAgICAgICAgIHJlcXVpcmVkOiAnZGF0YS1mb3JtdmFsaWRhdGlvbi1yZXF1aXJlZCcsXG4gICAgICAgICAgbWluTGVuZ3RoOiAnZGF0YS1mb3JtdmFsaWRhdGlvbi1taW5sZW5ndGgnLFxuICAgICAgICAgIG1heExlbmd0aDogJ2RhdGEtZm9ybXZhbGlkYXRpb24tbWF4bGVuZ3RoJyxcbiAgICAgICAgICBzZXRWYWx1ZXM6ICdkYXRhLWZvcm12YWxpZGF0aW9uLXNldHZhbHVlcycsXG4gICAgICAgICAgaW5wdXRUeXBlOiAnZGF0YS1mb3JtdmFsaWRhdGlvbi10eXBlJyxcbiAgICAgICAgICBzYW1lVmFsdWVBczogJ2RhdGEtZm9ybXZhbGlkYXRpb24tc2FtZXZhbHVlYXMnXG4gICAgICAgIH0pKSxcblxuICAgICAgICAvLyBPcHRpb25zXG4gICAgICAgIHJlbmRlcjogc2VsZi5zZXR0aW5ncy5yZW5kZXIsIC8vIHJlbmRlciB0aGUgZXJyb3JzL21lc3NhZ2VzIHRvIHRoZSBVSVxuICAgICAgICBzaG93U3VjY2Vzczogc2VsZi5zZXR0aW5ncy5zaG93U3VjY2Vzc09uRmllbGQsIC8vIHNob3cgdGhhdCB0aGUgaW5wdXQgc3VjY2Vzc2Z1bGx5IHZhbGlkYXRlZFxuICAgICAgICBzaG93RXJyb3I6IHNlbGYuc2V0dGluZ3Muc2hvd0Vycm9yT25GaWVsZCwgLy8gc2hvdyB0aGF0IHRoZSBpbnB1dCBlcnJvcmVkIG9uIHZhbGlkYXRpb25cbiAgICAgICAgc2hvd0FsbEVycm9yczogc2VsZi5zZXR0aW5ncy5zaG93QWxsRXJyb3JzLCAvLyBzaG93IGFsbCB0aGUgZXJyb3JzIG9uIHRoZSBmaWVsZCwgb3Igc2hvdyBvbmUgYnkgb25lXG5cbiAgICAgICAgLy8gRXZlbnRzIChpbiBvcmRlciBvZiBmaXJpbmcpXG4gICAgICAgIG9uYmVmb3JldmFsaWRhdGU6IHNlbGYuc2V0dGluZ3Mub25maWVsZGJlZm9yZXZhbGlkYXRlLFxuICAgICAgICBvbmFmdGVydmFsaWRhdGU6IHNlbGYuc2V0dGluZ3Mub25maWVsZGFmdGVydmFsaWRhdGUsXG4gICAgICAgIG9uc3VjY2Vzczogc2VsZi5zZXR0aW5ncy5vbmZpZWxkc3VjY2VzcyxcbiAgICAgICAgb25lcnJvcjogc2VsZi5zZXR0aW5ncy5vbmZpZWxkZXJyb3IsXG4gICAgICAgIG9uY29tcGxldGU6IHNlbGYuc2V0dGluZ3Mub25maWVsZGNvbXBsZXRlXG4gICAgICB9LCBFbGVtZW50QXR0cnNPYmplY3QoZWxlbSwge1xuICAgICAgICBydWxlczogJ2RhdGEtZm9ybXZhbGlkYXRpb24tcnVsZXMnLFxuICAgICAgICBzaG93U3VjY2VzczogJ2RhdGEtZm9ybXZhbGlkYXRpb24tc2hvd3N1Y2Nlc3MnLFxuICAgICAgICBzaG93RXJyb3I6ICdkYXRhLWZvcm12YWxpZGF0aW9uLXNob3dlcnJvcicsXG4gICAgICAgIHJlbmRlcjogJ2RhdGEtZm9ybXZhbGlkYXRpb24tcmVuZGVyJ1xuICAgICAgfSksIG9wdGlvbnMpXG4gICAgfVxuXG4gICAgLy8gSWYgcnVsZXMgd2FzIHNldCAoSlNPTiksIGV4cGFuZCBvdXQgaW50byB0aGUgb3B0aW9uc1xuICAgIGlmICh0eXBlb2YgZmllbGRWYWxpZGF0aW9uLnJ1bGVzID09PSAnc3RyaW5nJykge1xuICAgICAgdmFyIGNoZWNrUnVsZXMgPSBKU09OLnBhcnNlKGZpZWxkVmFsaWRhdGlvbi5ydWxlcylcbiAgICAgIGlmICh0eXBlb2YgY2hlY2tSdWxlcyA9PT0gJ29iamVjdCcpIHtcbiAgICAgICAgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMgPSAkLmV4dGVuZChmaWVsZFZhbGlkYXRpb24ub3B0aW9ucywgY2hlY2tSdWxlcylcbiAgICAgIH1cbiAgICB9XG5cbiAgICAvLyBBdXRvLXNlbGVjdCBpbnB1dFR5cGUgdmFsaWRhdGlvblxuICAgIGlmIChmaWVsZFZhbGlkYXRpb24udHlwZSA9PT0gJ2F1dG8nKSB7XG4gICAgICBmaWVsZFZhbGlkYXRpb24udHlwZSA9IGdldEZpZWxkVHlwZSgkZWxlbSlcbiAgICB9XG5cbiAgICAvLyBNYWtlIHN1cmUgdG8gYWx3YXlzIGNoZWNrIG5vbi10ZXh0IGlucHV0IHR5cGVzIHdpdGggdGhlIGlucHV0VHlwZSBydWxlXG4gICAgaWYgKGZpZWxkVmFsaWRhdGlvbi50eXBlICE9PSAndGV4dCcgJiYgKHR5cGVvZiBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlcy5pbnB1dFR5cGUgPT09ICd1bmRlZmluZWQnIHx8IGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLnJ1bGVzLmlucHV0VHlwZSA9PT0gZmFsc2UpKSB7XG4gICAgICBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlcy5pbnB1dFR5cGUgPSBmaWVsZFZhbGlkYXRpb24udHlwZVxuICAgIH1cblxuICAgIC8vIEBkZWJ1Z1xuICAgIC8vIGNvbnNvbGUubG9nKCdmaWVsZFZhbGlkYXRpb24nLCBmaWVsZFZhbGlkYXRpb24pXG5cbiAgICAvLyBCZWZvcmUgdmFsaWRhdGlvbiB5b3UgY2FuIHJ1biBhIGN1c3RvbSBjYWxsYmFjayB0byBwcm9jZXNzIHRoZSBmaWVsZCB2YWx1ZVxuICAgIGlmICh0eXBlb2YgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMub25iZWZvcmV2YWxpZGF0ZSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMub25iZWZvcmV2YWxpZGF0ZS5hcHBseSgkZWxlbVswXSwgW3NlbGYsIGZpZWxkVmFsaWRhdGlvbl0pXG4gICAgfVxuICAgIGZpZWxkVmFsaWRhdGlvbi4kZWxlbS50cmlnZ2VyKCdGb3JtVmFsaWRhdGlvbjp2YWxpZGF0ZUZpZWxkOmJlZm9yZXZhbGlkYXRlJywgW3NlbGYsIGZpZWxkVmFsaWRhdGlvbl0pXG5cbiAgICAvKlxuICAgICAqIEFwcGx5IHRoZSB2YWxpZGF0aW9uIHJ1bGVzXG4gICAgICovXG4gICAgZm9yICh2YXIgaSBpbiBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlcykge1xuICAgICAgLy8gQGRlYnVnXG4gICAgICAvLyBjb25zb2xlLmxvZygnZmllbGRWYWxpZGF0aW9uIHJ1bGVzOiAnICsgaSwgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMucnVsZXNbaV0pXG4gICAgICBpZiAoZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMucnVsZXNbaV0gJiYgc2VsZi5ydWxlcy5oYXNPd25Qcm9wZXJ0eShpKSAmJiB0eXBlb2Ygc2VsZi5ydWxlc1tpXSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAvLyBAZGVidWdcbiAgICAgICAgLy8gY29uc29sZS5sb2coJ3ZhbGlkYXRpbmcgdmlhIHJ1bGU6ICcgKyBpLCAnY29uZGl0aW9uOiAnLCBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlc1tpXSlcbiAgICAgICAgc2VsZi5ydWxlc1tpXS5hcHBseShzZWxmLCBbZmllbGRWYWxpZGF0aW9uLCBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlc1tpXV0pXG4gICAgICB9XG4gICAgfVxuXG4gICAgLy8gQWZ0ZXIgdmFsaWRhdGlvbiB5b3UgY2FuIHJ1biBhIGN1c3RvbSBjYWxsYmFjayBvbiB0aGUgcmVzdWx0cyBiZWZvcmUgc2hvd24gaW4gVUlcbiAgICBpZiAodHlwZW9mIGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLm9uYWZ0ZXJ2YWxpZGF0ZSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMub25hZnRlcnZhbGlkYXRlLmFwcGx5KCRlbGVtWzBdLCBbc2VsZiwgZmllbGRWYWxpZGF0aW9uXSlcbiAgICB9XG4gICAgZmllbGRWYWxpZGF0aW9uLiRlbGVtLnRyaWdnZXIoJ0Zvcm1WYWxpZGF0aW9uOnZhbGlkYXRlRmllbGQ6YWZ0ZXJ2YWxpZGF0ZScsIFtzZWxmLCBmaWVsZFZhbGlkYXRpb25dKVxuXG4gICAgLy8gRmllbGQgdmFsaWRhdGlvbiBlcnJvcnNcbiAgICBmaWVsZFZhbGlkYXRpb24uJGZvcm1GaWVsZC5yZW1vdmVDbGFzcygndWktZm9ybXZhbGlkYXRpb24tZXJyb3IgdWktZm9ybXZhbGlkYXRpb24tc3VjY2VzcycpXG4gICAgaWYgKGZpZWxkVmFsaWRhdGlvbi5lcnJvcnMubGVuZ3RoID4gMCkge1xuICAgICAgZmllbGRWYWxpZGF0aW9uLmlzVmFsaWQgPSBmYWxzZVxuXG4gICAgICAvLyBUcmlnZ2VyIGVycm9yXG4gICAgICBpZiAodHlwZW9mIGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLm9uZXJyb3IgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMub25lcnJvci5hcHBseShzZWxmLCBbc2VsZiwgZmllbGRWYWxpZGF0aW9uXSlcbiAgICAgIH1cbiAgICAgIGZpZWxkVmFsaWRhdGlvbi4kZWxlbS50cmlnZ2VyKCdGb3JtVmFsaWRhdGlvbjp2YWxpZGF0ZUZpZWxkOmVycm9yJywgW3NlbGYsIGZpZWxkVmFsaWRhdGlvbl0pXG5cbiAgICAgIC8vIFNob3cgZXJyb3IgbWVzc2FnZXMgb24gdGhlIGZpZWxkXG4gICAgICBpZiAoZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMuc2hvd0Vycm9yICYmIGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLnJlbmRlcikge1xuICAgICAgICAvLyBAZGVidWdcbiAgICAgICAgLy8gY29uc29sZS5sb2coJ1ZhbGlkYXRpb24gZXJyb3InLCBmaWVsZFZhbGlkYXRpb24uZXJyb3JzKVxuICAgICAgICBmaWVsZFZhbGlkYXRpb24uJGZvcm1GaWVsZC5hZGRDbGFzcygndWktZm9ybXZhbGlkYXRpb24tZXJyb3InKVxuICAgICAgICBmaWVsZFZhbGlkYXRpb24uJGZvcm1GaWVsZC5maW5kKCcudWktZm9ybXZhbGlkYXRpb24tbWVzc2FnZXMnKS5odG1sKCcnKVxuICAgICAgICBpZiAoZmllbGRWYWxpZGF0aW9uLmVycm9ycy5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgaWYgKGZpZWxkVmFsaWRhdGlvbi4kZm9ybUZpZWxkLmZpbmQoJy51aS1mb3JtdmFsaWRhdGlvbi1tZXNzYWdlcycpLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgZmllbGRWYWxpZGF0aW9uLiRmb3JtRmllbGQuYXBwZW5kKCc8dWwgY2xhc3M9XCJ1aS1mb3JtdmFsaWRhdGlvbi1tZXNzYWdlc1wiPjwvdWw+JylcbiAgICAgICAgICB9XG4gICAgICAgICAgdmFyIGZvcm1GaWVsZEVycm9yc0h0bWwgPSAnJ1xuICAgICAgICAgICQuZWFjaChmaWVsZFZhbGlkYXRpb24uZXJyb3JzLCBmdW5jdGlvbiAoaSwgaXRlbSkge1xuICAgICAgICAgICAgZm9ybUZpZWxkRXJyb3JzSHRtbCArPSAnPGxpPicgKyBpdGVtLmRlc2NyaXB0aW9uICsgJzwvbGk+J1xuICAgICAgICAgICAgaWYgKCFmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5zaG93QWxsRXJyb3JzKSByZXR1cm4gZmFsc2VcbiAgICAgICAgICB9KVxuICAgICAgICAgIGZpZWxkVmFsaWRhdGlvbi4kZm9ybUZpZWxkLmZpbmQoJy51aS1mb3JtdmFsaWRhdGlvbi1tZXNzYWdlcycpLmh0bWwoZm9ybUZpZWxkRXJyb3JzSHRtbClcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgLy8gRmllbGQgdmFsaWRhdGlvbiBzdWNjZXNzXG4gICAgfSBlbHNlIHtcbiAgICAgIGZpZWxkVmFsaWRhdGlvbi5pc1ZhbGlkID0gdHJ1ZVxuXG4gICAgICAvLyBUcmlnZ2VyIGVycm9yXG4gICAgICBpZiAodHlwZW9mIGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLm9uc3VjY2VzcyA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5vbnN1Y2Nlc3MuYXBwbHkoc2VsZiwgW3NlbGYsIGZpZWxkVmFsaWRhdGlvbl0pXG4gICAgICB9XG4gICAgICBmaWVsZFZhbGlkYXRpb24uJGVsZW0udHJpZ2dlcignRm9ybVZhbGlkYXRpb246dmFsaWRhdGVGaWVsZDpzdWNjZXNzJywgW3NlbGYsIGZpZWxkVmFsaWRhdGlvbl0pXG5cbiAgICAgIC8vIFNob3cgc3VjY2VzcyBtZXNzYWdlcyBvbiB0aGUgZmllbGRcbiAgICAgIGlmIChmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5zaG93U3VjY2VzcyAmJiBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5yZW5kZXIpIHtcbiAgICAgICAgLy8gQGRlYnVnXG4gICAgICAgIC8vIGNvbnNvbGUubG9nKCdWYWxpZGF0aW9uIHN1Y2Nlc3MnLCBmaWVsZFZhbGlkYXRpb24ubWVzc2FnZXMpXG4gICAgICAgIGZpZWxkVmFsaWRhdGlvbi4kZm9ybUZpZWxkLmFkZENsYXNzKCd1aS1mb3JtdmFsaWRhdGlvbi1zdWNjZXNzJylcbiAgICAgICAgZmllbGRWYWxpZGF0aW9uLiRmb3JtRmllbGQuZmluZCgnLnVpLWZvcm12YWxpZGF0aW9uLW1lc3NhZ2VzJykuaHRtbCgnJylcbiAgICAgICAgaWYgKGZpZWxkVmFsaWRhdGlvbi5tZXNzYWdlcy5sZW5ndGggPiAwKSB7XG4gICAgICAgICAgaWYgKGZpZWxkVmFsaWRhdGlvbi4kZm9ybUZpZWxkLmZpbmQoJy51aS1mb3JtdmFsaWRhdGlvbi1tZXNzYWdlcycpLmxlbmd0aCA9PT0gMCkge1xuICAgICAgICAgICAgZmllbGRWYWxpZGF0aW9uLiRmb3JtRmllbGQuYXBwZW5kKCc8dWwgY2xhc3M9XCJ1aS1mb3JtdmFsaWRhdGlvbi1tZXNzYWdlc1wiPjwvdWw+JylcbiAgICAgICAgICB9XG4gICAgICAgICAgdmFyIGZvcm1GaWVsZE1lc3NhZ2VzSHRtbCA9ICcnXG4gICAgICAgICAgJC5lYWNoKGZpZWxkVmFsaWRhdGlvbi5tZXNzYWdlcywgZnVuY3Rpb24gKGksIGl0ZW0pIHtcbiAgICAgICAgICAgIGZvcm1GaWVsZE1lc3NhZ2VzSHRtbCArPSAnPGxpPicgKyBpdGVtLmRlc2NyaXB0aW9uICsgJzwvbGk+J1xuICAgICAgICAgIH0pXG4gICAgICAgICAgZmllbGRWYWxpZGF0aW9uLiRmb3JtRmllbGQuZmluZCgnLnVpLWZvcm12YWxpZGF0aW9uLW1lc3NhZ2VzJykuaHRtbChmb3JtRmllbGRNZXNzYWdlc0h0bWwpXG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9XG5cbiAgICAvLyBAZGVidWdcbiAgICAvLyBjb25zb2xlLmxvZygnZmllbGRWYWxpZGF0aW9uJywgZmllbGRWYWxpZGF0aW9uKVxuXG4gICAgLy8gVHJpZ2dlciBjb21wbGV0ZVxuICAgIGlmICh0eXBlb2YgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMub25jb21wbGV0ZSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMub25jb21wbGV0ZS5hcHBseShzZWxmLCBbc2VsZiwgZmllbGRWYWxpZGF0aW9uXSlcbiAgICB9XG4gICAgZmllbGRWYWxpZGF0aW9uLiRlbGVtLnRyaWdnZXIoJ0Zvcm1WYWxpZGF0aW9uOnZhbGlkYXRlRmllbGQ6Y29tcGxldGUnLCBbc2VsZiwgZmllbGRWYWxpZGF0aW9uXSlcblxuICAgIHJldHVybiBmaWVsZFZhbGlkYXRpb25cbiAgfVxuXG4gIC8vIFZhbGlkYXRlIHRoZSBlbGVtZW50J3MgZmllbGRzXG4gIC8vIEByZXR1cm5zIHtCb29sZWFufVxuICBzZWxmLnZhbGlkYXRlID0gZnVuY3Rpb24gKG9wdGlvbnMpIHtcbiAgICB2YXIgc2VsZiA9IHRoaXNcblxuICAgIHZhciBncm91cFZhbGlkYXRpb24gPSAkLmV4dGVuZCh7XG4gICAgICAvLyBQcm9wZXJ0aWVzXG4gICAgICBpc1ZhbGlkOiBmYWxzZSxcbiAgICAgICRlbGVtOiBzZWxmLiRlbGVtLFxuICAgICAgJG5vdGlmaWNhdGlvbnM6IHNlbGYuJG5vdGlmaWNhdGlvbnMsXG4gICAgICBmaWVsZHM6IFtdLFxuICAgICAgdmFsaWRGaWVsZHM6IFtdLFxuICAgICAgZXJyb3JlZEZpZWxkczogW10sXG5cbiAgICAgIC8vIE9wdGlvbnNcbiAgICAgIHJlbmRlcjogc2VsZi5zZXR0aW5ncy5yZW5kZXIsXG5cbiAgICAgIC8vIEV2ZW50c1xuICAgICAgb25iZWZvcmV2YWxpZGF0ZTogc2VsZi5zZXR0aW5ncy5vbmJlZm9yZXZhbGlkYXRlLFxuICAgICAgb25hZnRlcnZhbGlkYXRlOiBzZWxmLnNldHRpbmdzLm9uYWZ0ZXJ2YWxpZGF0ZSxcbiAgICAgIG9uZXJyb3I6IHNlbGYuc2V0dGluZ3Mub25lcnJvcixcbiAgICAgIG9uc3VjY2Vzczogc2VsZi5zZXR0aW5ncy5vbnN1Y2Nlc3MsXG4gICAgICBvbmNvbXBsZXRlOiBzZWxmLnNldHRpbmdzLm9uY29tcGxldGVcbiAgICB9LCBvcHRpb25zKVxuXG4gICAgLy8gVHJpZ2dlciBiZWZvcmUgdmFsaWRhdGVcbiAgICBpZiAodHlwZW9mIGdyb3VwVmFsaWRhdGlvbi5vbmJlZm9yZXZhbGlkYXRlID09PSAnZnVuY3Rpb24nKSBncm91cFZhbGlkYXRpb24ub25iZWZvcmV2YWxpZGF0ZS5hcHBseShzZWxmLCBbc2VsZiwgZ3JvdXBWYWxpZGF0aW9uXSlcbiAgICBncm91cFZhbGlkYXRpb24uJGVsZW0udHJpZ2dlcignRm9ybVZhbGlkYXRpb246dmFsaWRhdGU6YmVmb3JldmFsaWRhdGUnLCBbc2VsZiwgZ3JvdXBWYWxpZGF0aW9uXSlcblxuICAgIC8vIFZhbGlkYXRlIGVhY2ggZmllbGRcbiAgICBzZWxmLmdldEZpZWxkcygpLmVhY2goZnVuY3Rpb24gKGksIGlucHV0KSB7XG4gICAgICB2YXIgZmllbGRWYWxpZGF0aW9uID0gc2VsZi52YWxpZGF0ZUZpZWxkKGlucHV0KVxuICAgICAgZ3JvdXBWYWxpZGF0aW9uLmZpZWxkcy5wdXNoKGZpZWxkVmFsaWRhdGlvbilcblxuICAgICAgLy8gRmlsdGVyIGNvbGxlY3Rpb24gdmlhIHZhbGlkL2Vycm9yZWRcbiAgICAgIGlmIChmaWVsZFZhbGlkYXRpb24uaXNWYWxpZCkge1xuICAgICAgICBncm91cFZhbGlkYXRpb24udmFsaWRGaWVsZHMucHVzaChmaWVsZFZhbGlkYXRpb24pXG4gICAgICB9IGVsc2Uge1xuICAgICAgICBncm91cFZhbGlkYXRpb24uZXJyb3JlZEZpZWxkcy5wdXNoKGZpZWxkVmFsaWRhdGlvbilcbiAgICAgIH1cbiAgICB9KVxuXG4gICAgLy8gVHJpZ2dlciBhZnRlciB2YWxpZGF0ZVxuICAgIGlmICh0eXBlb2YgZ3JvdXBWYWxpZGF0aW9uLm9uYWZ0ZXJ2YWxpZGF0ZSA9PT0gJ2Z1bmN0aW9uJykgZ3JvdXBWYWxpZGF0aW9uLm9uYWZ0ZXJ2YWxpZGF0ZS5hcHBseShzZWxmLCBbc2VsZiwgZ3JvdXBWYWxpZGF0aW9uXSlcbiAgICBncm91cFZhbGlkYXRpb24uJGVsZW0udHJpZ2dlcignRm9ybVZhbGlkYXRpb246dmFsaWRhdGU6YWZ0ZXJ2YWxpZGF0ZScsIFtzZWxmLCBncm91cFZhbGlkYXRpb25dKVxuXG4gICAgLy8gRXJyb3JcbiAgICBncm91cFZhbGlkYXRpb24uJG5vdGlmaWNhdGlvbnMuaHRtbCgnJylcbiAgICBpZiAoZ3JvdXBWYWxpZGF0aW9uLmVycm9yZWRGaWVsZHMubGVuZ3RoID4gMCkge1xuICAgICAgZ3JvdXBWYWxpZGF0aW9uLmlzVmFsaWQgPSBmYWxzZVxuICAgICAgaWYgKHR5cGVvZiBncm91cFZhbGlkYXRpb24ub25lcnJvciA9PT0gJ2Z1bmN0aW9uJykgZ3JvdXBWYWxpZGF0aW9uLm9uZXJyb3IuYXBwbHkoc2VsZiwgW3NlbGYsIGdyb3VwVmFsaWRhdGlvbl0pXG4gICAgICBncm91cFZhbGlkYXRpb24uJGVsZW0udHJpZ2dlcignRm9ybVZhbGlkYXRpb246dmFsaWRhdGU6ZXJyb3InLCBbc2VsZiwgZ3JvdXBWYWxpZGF0aW9uXSlcblxuICAgICAgLy8gUmVuZGVyIHRvIHZpZXdcbiAgICAgIGlmIChncm91cFZhbGlkYXRpb24ucmVuZGVyKSB7XG4gICAgICAgIGdyb3VwVmFsaWRhdGlvbi4kbm90aWZpY2F0aW9ucy5odG1sKCc8ZGl2IGNsYXNzPVwibWVzc2FnZS1lcnJvclwiPjxwPlRoZXJlIGFyZSBlcnJvcnMgd2l0aCB0aGUgZm9ybSBiZWxvdy4gUGxlYXNlIGNoZWNrIGVuc3VyZSB5b3VyIGluZm9ybWF0aW9uIGhhcyBiZWVuIGVudGVyZWQgY29ycmVjdGx5IGJlZm9yZSBjb250aW51aW5nLjwvcD48L2Rpdj4nKVxuICAgICAgfVxuXG4gICAgLy8gU3VjY2Vzc1xuICAgIH0gZWxzZSB7XG4gICAgICBncm91cFZhbGlkYXRpb24uaXNWYWxpZCA9IHRydWVcbiAgICAgIGlmICh0eXBlb2YgZ3JvdXBWYWxpZGF0aW9uLm9uc3VjY2VzcyA9PT0gJ2Z1bmN0aW9uJykgZ3JvdXBWYWxpZGF0aW9uLm9uc3VjY2Vzcy5hcHBseShzZWxmLCBbc2VsZiwgZ3JvdXBWYWxpZGF0aW9uXSlcbiAgICAgIGdyb3VwVmFsaWRhdGlvbi4kZWxlbS50cmlnZ2VyKCdGb3JtVmFsaWRhdGlvbjp2YWxpZGF0ZTpzdWNjZXNzJywgW3NlbGYsIGdyb3VwVmFsaWRhdGlvbl0pXG4gICAgfVxuXG4gICAgLy8gVHJpZ2dlciBjb21wbGV0ZVxuICAgIGlmICh0eXBlb2YgZ3JvdXBWYWxpZGF0aW9uLm9uY29tcGxldGUgPT09ICdmdW5jdGlvbicpIGdyb3VwVmFsaWRhdGlvbi5vbmNvbXBsZXRlLmFwcGx5KHNlbGYsIFtzZWxmLCBncm91cFZhbGlkYXRpb25dKVxuICAgIGdyb3VwVmFsaWRhdGlvbi4kZWxlbS50cmlnZ2VyKCdGb3JtVmFsaWRhdGlvbjp2YWxpZGF0ZTpjb21wbGV0ZScsIFtzZWxmLCBncm91cFZhbGlkYXRpb25dKVxuXG4gICAgcmV0dXJuIGdyb3VwVmFsaWRhdGlvblxuICB9XG5cbiAgLy8gQ2xlYXJzIGdyb3VwJ3MgZm9ybSBmaWVsZHMgb2YgZXJyb3JzXG4gIHNlbGYuY2xlYXIgPSBmdW5jdGlvbiAoKSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG4gICAgc2VsZi5nZXRGaWVsZHMoKS5lYWNoKGZ1bmN0aW9uIChpLCBpbnB1dCkge1xuICAgICAgJChlbGVtKVxuICAgICAgICAucGFyZW50cygnLmZvcm0tZmllbGQnKS5yZW1vdmVDbGFzcygndWktZm9ybXZhbGlkYXRpb24tZXJyb3IgdWktZm9ybXZhbGlkYXRpb24tc3VjY2VzcycpXG4gICAgICAgIC5maW5kKCcudWktZm9ybXZhbGlkYXRpb24tbWVzc2FnZXMnKS5odG1sKCcnKVxuICAgIH0pXG4gICAgc2VsZi4kbm90aWZpY2F0aW9ucy5odG1sKCcnKVxuICAgIHNlbGYuJGVsZW0udHJpZ2dlcignRm9ybVZhbGlkYXRpb246Y2xlYXInLCBbc2VsZl0pXG4gIH1cblxuICAvLyBDbGVhcnMgd2hvbGUgZm9ybSAoYWxsIGdyb3VwcylcbiAgc2VsZi5jbGVhckFsbCA9IGZ1bmN0aW9uICgpIHtcbiAgICB2YXIgc2VsZiA9IHRoaXNcbiAgICBzZWxmLiRmb3JtLnVpRm9ybVZhbGlkYXRpb24oJ2NsZWFyJylcbiAgICAgIC5maW5kKCdbZGF0YS1mb3JtdmFsaWRhdGlvbl0nKS51aUZvcm1WYWxpZGF0aW9uKCdjbGVhcicpXG4gIH1cblxuICAvLyBHZXQgdGhlIGNvbGxlY3Rpb24gb2YgZmllbGRzXG4gIHNlbGYuZ2V0RmllbGRzID0gZnVuY3Rpb24gKCkge1xuICAgIHZhciBzZWxmID0gdGhpc1xuICAgIHJldHVybiBzZWxmLiRlbGVtLmZpbmQoJ1tkYXRhLWZvcm12YWxpZGF0aW9uLWZpZWxkXScpXG4gIH1cblxuICAvLyBFdmVudHMgb24gdGhlIGZvcm1cbiAgc2VsZi4kZm9ybS5vbihzZWxmLnNldHRpbmdzLndhdGNoRm9ybUV2ZW50cywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgaWYgKHNlbGYuc2V0dGluZ3MudmFsaWRhdGVPbkZvcm1FdmVudHMpIHtcbiAgICAgIHZhciBmb3JtVmFsaWRhdGlvbiA9IHtcbiAgICAgICAgaXNWYWxpZDogZmFsc2UsXG4gICAgICAgIGdyb3VwczogW10sXG4gICAgICAgIHZhbGlkR3JvdXBzOiBbXSxcbiAgICAgICAgZXJyb3JlZEdyb3VwczogW11cbiAgICAgIH1cblxuICAgICAgLy8gVmFsaWRhdGUgZWFjaCBncm91cCB3aXRoaW4gdGhlIGZvcm1cbiAgICAgICQodGhpcykuZmluZCgnW2RhdGEtZm9ybXZhbGlkYXRpb25dJykuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgICAgICB2YXIgZ3JvdXBWYWxpZGF0aW9uID0gZWxlbS5Gb3JtVmFsaWRhdGlvbi52YWxpZGF0ZSgpXG4gICAgICAgIGZvcm1WYWxpZGF0aW9uLmdyb3Vwcy5wdXNoKGdyb3VwVmFsaWRhdGlvbilcblxuICAgICAgICAvLyBWYWxpZCBncm91cFxuICAgICAgICBpZiAoZ3JvdXBWYWxpZGF0aW9uLmlzVmFsaWQpIHtcbiAgICAgICAgICBmb3JtVmFsaWRhdGlvbi52YWxpZEdyb3Vwcy5wdXNoKGdyb3VwVmFsaWRhdGlvbilcblxuICAgICAgICAvLyBJbnZhbGlkIGdyb3VwXG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgZm9ybVZhbGlkYXRpb24uZXJyb3JlZEdyb3Vwcy5wdXNoKGdyb3VwVmFsaWRhdGlvbilcbiAgICAgICAgfVxuICAgICAgfSlcblxuICAgICAgLy8gRXJyb3JcbiAgICAgIGlmIChmb3JtVmFsaWRhdGlvbi5lcnJvcmVkR3JvdXBzLmxlbmd0aCA+IDApIHtcbiAgICAgICAgZm9ybVZhbGlkYXRpb24uaXNWYWxpZCA9IGZhbHNlXG4gICAgICAgIGlmICh0eXBlb2Ygc2VsZi5zZXR0aW5ncy5vbmVycm9yID09PSAnZnVuY3Rpb24nKSBzZWxmLnNldHRpbmdzLm9uZXJyb3IuYXBwbHkoc2VsZiwgW3NlbGYsIGZvcm1WYWxpZGF0aW9uXSlcbiAgICAgICAgJCh0aGlzKS50cmlnZ2VyKCdGb3JtVmFsaWRhdGlvbjplcnJvcicsIFtzZWxmLCBmb3JtVmFsaWRhdGlvbl0pXG5cbiAgICAgIC8vIFN1Y2Nlc3NcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIGZvcm1WYWxpZGF0aW9uLmlzVmFsaWQgPSB0cnVlXG4gICAgICAgIGlmICh0eXBlb2Ygc2VsZi5zZXR0aW5ncy5vbnN1Y2Nlc3MgPT09ICdmdW5jdGlvbicpIHNlbGYuc2V0dGluZ3Mub25zdWNjZXNzLmFwcGx5KHNlbGYsIFtzZWxmLCBmb3JtVmFsaWRhdGlvbl0pXG4gICAgICAgICQodGhpcykudHJpZ2dlcignRm9ybVZhbGlkYXRpb246c3VjY2VzcycsIFtzZWxmLCBmb3JtVmFsaWRhdGlvbl0pXG4gICAgICB9XG5cbiAgICAgIC8vIFN0b3AgYW55IHN1Ym1pdHRpbmcgaGFwcGVuaW5nXG4gICAgICBpZiAoIWZvcm1WYWxpZGF0aW9uLmlzVmFsaWQpIHJldHVybiBmYWxzZVxuICAgIH1cbiAgfSlcblxuICAvLyBFdmVudHMgb24gdGhlIGZpZWxkc1xuICBzZWxmLmdldEZpZWxkcygpLm9uKHNlbGYuc2V0dGluZ3Mud2F0Y2hGaWVsZEV2ZW50cywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgaWYgKHNlbGYuc2V0dGluZ3MudmFsaWRhdGVPbkZpZWxkRXZlbnRzKSB7XG4gICAgICBzZWxmLnZhbGlkYXRlRmllbGQoZXZlbnQudGFyZ2V0KVxuICAgIH1cbiAgfSlcblxuICAvLyBjb25zb2xlLmxvZyhzZWxmLmdldEZpZWxkcygpKVxuXG4gIC8vIEF0dGFjaCBGb3JtVmFsaWRhdGlvbiBpbnN0YW5jZSB0byBlbGVtZW50XG4gIHNlbGYuJGVsZW1bMF0uRm9ybVZhbGlkYXRpb24gPSBzZWxmXG4gIHJldHVybiBzZWxmXG59XG5cbi8qXG4gKiBQcm90b3R5cGUgZnVuY3Rpb25zIGFuZCBwcm9wZXJ0aWVzIHNoYXJlZCBiZXR3ZWVuIGFsbCBpbnN0YW5jZXMgb2YgRm9ybVZhbGlkYXRpb25cbiAqL1xuLy8gVGhlIGZpZWxkIHZhbGlkYXRpb24gcnVsZXMgdG8gYXBwbHlcbi8vIFlvdSBjYW4gYWRkIGN1c3RvbSBuZXcgcnVsZXMgYnkgYWRkaW5nIHRvIHRoZSBwcm90b3R5cGUgb2JqZWN0XG5Gb3JtVmFsaWRhdGlvbi5wcm90b3R5cGUucnVsZXMgPSB7XG4gIC8vIERlZmF1bHQgcnVsZXMgcGVyIGZpZWxkIHZhbGlkYXRpb25cbiAgZGVmYXVsdFJ1bGVzOiB7XG4gICAgcmVxdWlyZWQ6IGZhbHNlLCAgLy8gaWYgdGhlIGlucHV0IGlzIHJlcXVpcmVkXG4gICAgbWluTGVuZ3RoOiBmYWxzZSwgLy8gdGhlIG1pbmltdW0gbGVuZ3RoIG9mIHRoZSBpbnB1dFxuICAgIG1heExlbmd0aDogZmFsc2UsIC8vIHRoZSBtYXhpbXVtIGxlbmd0aCBvZiB0aGUgaW5wdXRcbiAgICBzZXRWYWx1ZXM6IGZhbHNlLCAvLyBsaXN0IG9mIHBvc3NpYmxlIHNldCB2YWx1ZXMgdG8gbWF0Y2ggdG8sIGUuZy4gWydvbicsICdvZmYnXVxuICAgIGlucHV0VHlwZTogZmFsc2UsIC8vIGEga2V5d29yZCB0aGF0IG1hdGNoZXMgdGhlIGlucHV0IHRvIGEgYW4gaW5wdXQgdHlwZSwgZS5nLiAndGV4dCcsICdudW1iZXInLCAnZW1haWwnLCAnZGF0ZScsICd1cmwnLCBldGMuXG4gICAgc2FtZVZhbHVlQXM6IGZhbHNlLCAvLyB7U3RyaW5nfSBzZWxlY3Rvciwge0hUTUxFbGVtZW50fSwge2pRdWVyeU9iamVjdH1cbiAgICBjdXN0b206IGZhbHNlIC8vIHtGdW5jdGlvbn0gZnVuY3Rpb24gKGZpZWxkVmFsaWRhdGlvbikgeyAuLnBlcmZvcm0gdmFsaWRhdGlvbiB2aWEgZmllbGRWYWxpZGF0aW9uIG9iamVjdC4uIH1cbiAgfSxcblxuICAvLyBGaWVsZCBtdXN0IGhhdmUgdmFsdWUgKGkuZS4gbm90IG51bGwgb3IgdW5kZWZpbmVkKVxuICByZXF1aXJlZDogZnVuY3Rpb24gKGZpZWxkVmFsaWRhdGlvbiwgaXNSZXF1aXJlZCkge1xuICAgIC8vIEZvcm1WYWxpZGF0aW9uXG4gICAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgICAvLyBEZWZhdWx0IHRvIGZpZWxkVmFsaWRhdGlvbiBvcHRpb24gcnVsZSB2YWx1ZVxuICAgIGlmICh0eXBlb2YgaXNSZXF1aXJlZCA9PT0gJ3VuZGVmaW5lZCcpIGlzUmVxdWlyZWQgPSBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlcy5pc1JlcXVpcmVkXG5cbiAgICBpZiAoaXNSZXF1aXJlZCAmJiAodHlwZW9mIGZpZWxkVmFsaWRhdGlvbi52YWx1ZSA9PT0gJ3VuZGVmaW5lZCcgfHwgdHlwZW9mIGZpZWxkVmFsaWRhdGlvbi52YWx1ZSA9PT0gJ251bGwnIHx8IGZpZWxkVmFsaWRhdGlvbi52YWx1ZSA9PT0gJycpKSB7XG5cbiAgICAgIC8vIENoZWNrYm94IGlzIGVtcHR5XG4gICAgICBpZiAoZmllbGRWYWxpZGF0aW9uLnR5cGUgPT09ICdjaGVja2JveCcpIHtcbiAgICAgICAgZmllbGRWYWxpZGF0aW9uLmVycm9ycy5wdXNoKHtcbiAgICAgICAgICB0eXBlOiAncmVxdWlyZWQnLFxuICAgICAgICAgIGRlc2NyaXB0aW9uOiBfXy5fXygnUGxlYXNlIGNoZWNrIHRoZSBib3ggdG8gY29udGludWUnLCAnZXJyb3JGaWVsZFJlcXVpcmVkQ2hlY2tib3gnKVxuICAgICAgICB9KVxuXG4gICAgICAvLyBNdWx0aXBsZSBjaGVja2JveGVzIGFyZSBlbXB0eVxuICAgICAgfSBlbHNlIGlmIChmaWVsZFZhbGlkYXRpb24udHlwZSA9PT0gJ211bHRpIGNoZWNrYm94Jykge1xuICAgICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICAgIHR5cGU6ICdyZXF1aXJlZCcsXG4gICAgICAgICAgZGVzY3JpcHRpb246IF9fLl9fKCdQbGVhc2Ugc2VsZWN0IGFuIG9wdGlvbiB0byBjb250aW51ZScsICdlcnJvckZpZWxkUmVxdWlyZWRDaGVja2JveGVzJylcbiAgICAgICAgfSlcblxuICAgICAgLy8gUmFkaW8gaXMgZW1wdHlcbiAgICAgIH0gZWxzZSBpZiAoZmllbGRWYWxpZGF0aW9uLnR5cGUgPT09ICdyYWRpbycgfHwgZmllbGRWYWxpZGF0aW9uLnR5cGUgPT09ICdtdWx0aSByYWRpbycpIHtcbiAgICAgICAgZmllbGRWYWxpZGF0aW9uLmVycm9ycy5wdXNoKHtcbiAgICAgICAgICB0eXBlOiAncmVxdWlyZWQnLFxuICAgICAgICAgIGRlc2NyaXB0aW9uOiBfXy5fXygnUGxlYXNlIHNlbGVjdCBhbiBvcHRpb24gdG8gY29udGludWUnLCAnZXJyb3JGaWVsZFJlcXVpcmVkUmFkaW8nKVxuICAgICAgICB9KVxuXG4gICAgICB9IGVsc2UgaWYgKGZpZWxkVmFsaWRhdGlvbi50eXBlID09PSAnc2VsZWN0Jykge1xuICAgICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICAgIHR5cGU6ICdyZXF1aXJlZCcsXG4gICAgICAgICAgZGVzY3JpcHRpb246IF9fLl9fKCdQbGVhc2Ugc2VsZWN0IGFuIG9wdGlvbiB0byBjb250aW51ZScsICdlcnJvckZpZWxkUmVxdWlyZWRTZWxlY3QnKVxuICAgICAgICB9KVxuXG4gICAgICAvLyBPdGhlciB0eXBlIG9mIGlucHV0IGlzIGVtcHR5XG4gICAgICB9IGVsc2Uge1xuICAgICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICAgIHR5cGU6ICdyZXF1aXJlZCcsXG4gICAgICAgICAgZGVzY3JpcHRpb246IF9fLl9fKCdGaWVsZCB2YWx1ZSBjYW5ub3QgYmUgZW1wdHknLCAnZXJyb3JGaWVsZFJlcXVpcmVkJylcbiAgICAgICAgfSlcbiAgICAgIH1cbiAgICB9XG5cbiAgICAvLyBAZGVidWdcbiAgICAvLyBjb25zb2xlLmxvZygncnVsZXMucmVxdWlyZWQnLCBmaWVsZFZhbGlkYXRpb24pXG4gIH0sXG5cbiAgLy8gTWluaW11bSBsZW5ndGhcbiAgbWluTGVuZ3RoOiBmdW5jdGlvbiAoZmllbGRWYWxpZGF0aW9uLCBtaW5MZW5ndGgpIHtcbiAgICAvLyBGb3JtVmFsaWRhdGlvblxuICAgIHZhciBzZWxmID0gdGhpc1xuXG4gICAgLy8gRGVmYXVsdCB0byBmaWVsZFZhbGlkYXRpb24gb3B0aW9uIHJ1bGUgdmFsdWVcbiAgICBpZiAodHlwZW9mIG1pbkxlbmd0aCA9PT0gJ3VuZGVmaW5lZCcpIG1pbkxlbmd0aCA9IGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLnJ1bGVzLm1pbkxlbmd0aFxuXG4gICAgaWYgKG1pbkxlbmd0aCAmJiBmaWVsZFZhbGlkYXRpb24udmFsdWUubGVuZ3RoIDwgbWluTGVuZ3RoKSB7XG4gICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICB0eXBlOiAnbWluTGVuZ3RoJyxcbiAgICAgICAgZGVzY3JpcHRpb246IHNwcmludGYoX18uX18oJ1BsZWFzZSBlbnN1cmUgZmllbGQgaXMgYXQgbGVhc3QgJWQgY2hhcmFjdGVycyBsb25nJywgJ2Vycm9yRmllbGRNaW5MZW5ndGgnKSwgbWluTGVuZ3RoKVxuICAgICAgfSlcbiAgICB9XG4gIH0sXG5cbiAgLy8gTWF4aW11bSBsZW5ndGhcbiAgbWF4TGVuZ3RoOiBmdW5jdGlvbiAoZmllbGRWYWxpZGF0aW9uLCBtYXhMZW5ndGgpIHtcbiAgICAvLyBGb3JtVmFsaWRhdGlvblxuICAgIHZhciBzZWxmID0gdGhpc1xuXG4gICAgLy8gRGVmYXVsdCB0byBmaWVsZFZhbGlkYXRpb24gb3B0aW9uIHJ1bGUgdmFsdWVcbiAgICBpZiAodHlwZW9mIG1heExlbmd0aCA9PT0gJ3VuZGVmaW5lZCcpIG1heExlbmd0aCA9IGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLnJ1bGVzLm1heExlbmd0aFxuXG4gICAgaWYgKG1heExlbmd0aCAmJiBmaWVsZFZhbGlkYXRpb24udmFsdWUubGVuZ3RoID4gbWF4TGVuZ3RoKSB7XG4gICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICB0eXBlOiAnbWF4TGVuZ3RoJyxcbiAgICAgICAgZGVzY3JpcHRpb246IHNwcmludGYoX18uX18oJ1BsZWFzZSBlbnN1cmUgZmllbGQgZG9lcyBub3QgZXhjZWVkICVkIGNoYXJhY3RlcnMnLCAnZXJyb3JGaWVsZE1heExlbmd0aCcpLCBtYXhMZW5ndGgpXG4gICAgICB9KVxuICAgIH1cbiAgfSxcblxuICAvLyBTZXQgVmFsdWVzXG4gIHNldFZhbHVlczogZnVuY3Rpb24gKGZpZWxkVmFsaWRhdGlvbiwgc2V0VmFsdWVzKSB7XG4gICAgLy8gRm9ybVZhbGlkYXRpb25cbiAgICB2YXIgc2VsZiA9IHRoaXNcblxuICAgIC8vIERlZmF1bHQgdG8gZmllbGRWYWxpZGF0aW9uIG9wdGlvbiBydWxlIHZhbHVlXG4gICAgaWYgKHR5cGVvZiBzZXRWYWx1ZXMgPT09ICd1bmRlZmluZWQnKSBzZXRWYWx1ZXMgPSBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlcy5zZXRWYWx1ZXNcblxuICAgIGlmIChzZXRWYWx1ZXMpIHtcbiAgICAgIC8vIENvbnZlcnQgc3RyaW5nIHRvIGFycmF5XG4gICAgICBpZiAodHlwZW9mIHNldFZhbHVlcyA9PT0gJ3N0cmluZycpIHtcbiAgICAgICAgaWYgKC9bXFxzLF0rLy50ZXN0KHNldFZhbHVlcykpIHtcbiAgICAgICAgICBzZXRWYWx1ZXMgPSBzZXRWYWx1ZXMuc3BsaXQoL1tcXHMsXSsvKVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgIHNldFZhbHVlcyA9IFtzZXRWYWx1ZXNdXG4gICAgICAgIH1cbiAgICAgIH1cblxuICAgICAgLy8gQ2hlY2sgaWYgdmFsdWUgY29ycmVzcG9uZHMgdG8gb25lIG9mIHRoZSBzZXQgdmFsdWVzXG4gICAgICBpZiAoISQuaW5BcnJheShmaWVsZFZhbGlkYXRpb24udmFsdWUsIGZpZWxkVmFsaWRhdGlvbi5vcHRpb25zLnNldFZhbHVlcykpIHtcbiAgICAgICAgZmllbGRWYWxpZGF0aW9uLmVycm9ycy5wdXNoKHtcbiAgICAgICAgICB0eXBlOiAnc2V0VmFsdWVzJyxcbiAgICAgICAgICBkZXNjcmlwdGlvbjogX18uX18oJ0ZpZWxkIHZhbHVlIG5vdCBhY2NlcHRlZCcsICdlcnJvckZpZWxkU2V0VmFsdWVzJylcbiAgICAgICAgfSlcbiAgICAgIH1cbiAgICB9XG4gIH0sXG5cbiAgLy8gSW5wdXQgVHlwZVxuICBpbnB1dFR5cGU6IGZ1bmN0aW9uIChmaWVsZFZhbGlkYXRpb24sIGlucHV0VHlwZSkge1xuICAgIC8vIEZvcm1WYWxpZGF0aW9uXG4gICAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgICAvLyBEZWZhdWx0IHRvIGZpZWxkVmFsaWRhdGlvbiBvcHRpb24gcnVsZSB2YWx1ZVxuICAgIGlmICh0eXBlb2YgaW5wdXRUeXBlID09PSAndW5kZWZpbmVkJykgaW5wdXRUeXBlID0gZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMucnVsZXMuaW5wdXRUeXBlXG5cbiAgICBpZiAoaW5wdXRUeXBlKSB7XG4gICAgICBzd2l0Y2ggKGlucHV0VHlwZS50b0xvd2VyQ2FzZSgpKSB7XG4gICAgICAgIGNhc2UgJ251bWJlcic6XG4gICAgICAgICAgaWYgKC9bXlxcZC1cXC5dKy8udGVzdChmaWVsZFZhbGlkYXRpb24udmFsdWUpKSB7XG4gICAgICAgICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICAgICAgICB0eXBlOiAnaW5wdXRUeXBlJyxcbiAgICAgICAgICAgICAgZGVzY3JpcHRpb246IF9fLl9fKCdGaWVsZCBhY2NlcHRzIG9ubHkgbnVtYmVycycsICdlcnJvckZpZWxkSW5wdXRUeXBlTnVtYmVyJylcbiAgICAgICAgICAgIH0pXG4gICAgICAgICAgfVxuICAgICAgICAgIGJyZWFrXG5cbiAgICAgICAgY2FzZSAncGhvbmUnOlxuICAgICAgICBjYXNlICd0ZWxlcGhvbmUnOlxuICAgICAgICBjYXNlICdtb2JpbGUnOlxuICAgICAgICAgIC8vIEFsbG93ZWQ6ICszMyA2NDQgOTExIDI1MFxuICAgICAgICAgIC8vICAgICAgICAgICgwKSAxMi4zNC41Ni43OC45MFxuICAgICAgICAgIC8vICAgICAgICAgIDg1Ni02Njg4XG4gICAgICAgICAgaWYgKCEvXlxcKz9bMC05XFwtXFwuIF17Nix9JC8udGVzdChmaWVsZFZhbGlkYXRpb24udmFsdWUpKSB7XG4gICAgICAgICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICAgICAgICB0eXBlOiAnaW5wdXRUeXBlJyxcbiAgICAgICAgICAgICAgZGVzY3JpcHRpb246IF9fLl9fKCdOb3QgYSB2YWxpZCB0ZWxlcGhvbmUgbnVtYmVyJywgJ2Vycm9yRmllbGRJbnB1dFR5cGVQaG9uZScpXG4gICAgICAgICAgICB9KVxuICAgICAgICAgIH1cbiAgICAgICAgICBicmVha1xuXG4gICAgICAgIGNhc2UgJ2VtYWlsJzpcbiAgICAgICAgICAvLyBBbGxvd2VkOiBtYXR0LnNjaGV1cmljaEBleGFtcGxlLmNvbVxuICAgICAgICAgIC8vICAgICAgICAgIG1hdHRzY2hldXJpY2hAZXhhbXAubGUuY29tXG4gICAgICAgICAgLy8gICAgICAgICAgbWF0dHNjaGV1cmljaDE5ODNAZXhhbXBsZS1lbWFpbC5jby5uelxuICAgICAgICAgIC8vICAgICAgICAgIG1hdHRfc2NoZXVyaWNoQGV4YW1wbGUuZW1haWwuYWRkcmVzcy5uZXQubnpcbiAgICAgICAgICBpZiAoIS9eW2EtejAtOVxcLV9cXC5dK1xcQFthLXowLTlcXC1cXC5dK1xcLlthLXowLTldezIsfSg/OlxcLlthLXowLTldezIsfSkqJC9pLnRlc3QoZmllbGRWYWxpZGF0aW9uLnZhbHVlKSkge1xuICAgICAgICAgICAgZmllbGRWYWxpZGF0aW9uLmVycm9ycy5wdXNoKHtcbiAgICAgICAgICAgICAgdHlwZTogJ2lucHV0VHlwZScsXG4gICAgICAgICAgICAgIGRlc2NyaXB0aW9uOiBfXy5fXygnTm90IGEgdmFsaWQgZW1haWwgYWRkcmVzcycsICdlcnJvckZpZWxkSW5wdXRUeXBlRW1haWwnKVxuICAgICAgICAgICAgfSlcbiAgICAgICAgICB9XG4gICAgICAgICAgYnJlYWtcblxuICAgICAgICBjYXNlICdpYmFuJzpcbiAgICAgICAgICAvLyBVc2VzIG5wbSBsaWJyYXJ5IGBpYmFuYCB0byB2YWxpZGF0ZVxuICAgICAgICAgIGlmICghSWJhbi5pc1ZhbGlkKGZpZWxkVmFsaWRhdGlvbi52YWx1ZS5yZXBsYWNlKC9cXHMrL2csICcnKSkpIHtcbiAgICAgICAgICAgIGZpZWxkVmFsaWRhdGlvbi5lcnJvcnMucHVzaCh7XG4gICAgICAgICAgICAgIHR5cGU6ICdpbnB1dFR5cGUnLFxuICAgICAgICAgICAgICBkZXNjcmlwdGlvbjogX18uX18oJ05vdCBhIHZhbGlkIElCQU4gbnVtYmVyLiBQbGVhc2UgZW5zdXJlIHlvdSBoYXZlIGVudGVyZWQgeW91ciBudW1iZXIgaW4gY29ycmVjdGx5JywgJ2Vycm9yRmllbGRJbnB1dFR5cGVJYmFuJylcbiAgICAgICAgICAgIH0pXG4gICAgICAgICAgfVxuICAgICAgICAgIGJyZWFrXG4gICAgICB9XG4gICAgfVxuICB9LFxuXG4gIC8vIFNhbWUgVmFsdWUgYXNcbiAgc2FtZVZhbHVlQXM6IGZ1bmN0aW9uIChmaWVsZFZhbGlkYXRpb24sIHNhbWVWYWx1ZUFzKSB7XG4gICAgLy8gRm9ybVZhbGlkYXRpb25cbiAgICB2YXIgc2VsZiA9IHRoaXNcblxuICAgIC8vIERlZmF1bHQgdG8gZmllbGRWYWxpZGF0aW9uIG9wdGlvbiBydWxlIHZhbHVlXG4gICAgaWYgKHR5cGVvZiBzYW1lVmFsdWVBcyA9PT0gJ3VuZGVmaW5lZCcpIHNhbWVWYWx1ZUFzID0gZmllbGRWYWxpZGF0aW9uLm9wdGlvbnMucnVsZXMuc2FtZVZhbHVlQXNcblxuICAgIGlmIChzYW1lVmFsdWVBcykge1xuICAgICAgdmFyICRjb21wYXJlRWxlbSA9ICQoc2FtZVZhbHVlQXMpXG4gICAgICBpZiAoJGNvbXBhcmVFbGVtLmxlbmd0aCA+IDApIHtcbiAgICAgICAgaWYgKCRjb21wYXJlRWxlbS52YWwoKSAhPSBmaWVsZFZhbGlkYXRpb24udmFsdWUpIHtcbiAgICAgICAgICB2YXIgY29tcGFyZUVsZW1MYWJlbCA9IGdldExhYmVsRm9yRWxlbSgkY29tcGFyZUVsZW0pLnJlcGxhY2UoL1xcKi4qJC9pLCAnJylcbiAgICAgICAgICBmaWVsZFZhbGlkYXRpb24uZXJyb3JzLnB1c2goe1xuICAgICAgICAgICAgdHlwZTogJ3NhbWVWYWx1ZUFzJyxcbiAgICAgICAgICAgIGRlc2NyaXB0aW9uOiBzcHJpbnRmKF9fLl9fKCdGaWVsZCBkb2VzblxcJ3QgbWF0Y2ggJXMnLCAnZXJyb3JGaWVsZFNhbWVWYWx1ZUFzJyksICc8bGFiZWwgZm9yPVwiJyArICRjb21wYXJlRWxlbS5hdHRyKCdpZCcpICsgJ1wiPjxzdHJvbmc+JyArIGNvbXBhcmVFbGVtTGFiZWwgKyAnPC9zdHJvbmc+PC9sYWJlbD4nKVxuICAgICAgICAgIH0pXG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9XG4gIH0sXG5cbiAgLy8gQ3VzdG9tXG4gIGN1c3RvbTogZnVuY3Rpb24gKGZpZWxkVmFsaWRhdGlvbiwgY3VzdG9tKSB7XG4gICAgLy8gRm9ybVZhbGlkYXRpb25cbiAgICB2YXIgc2VsZiA9IHRoaXNcblxuICAgIC8vIERlZmF1bHQgdG8gZmllbGRWYWxpZGF0aW9uIG9wdGlvbiBydWxlIHZhbHVlXG4gICAgaWYgKHR5cGVvZiBjdXN0b20gPT09ICd1bmRlZmluZWQnKSBjdXN0b20gPSBmaWVsZFZhbGlkYXRpb24ub3B0aW9ucy5ydWxlcy5jdXN0b21cblxuICAgIGlmICh0eXBlb2YgY3VzdG9tID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAvLyBGb3IgY3VzdG9tIHZhbGlkYXRpb25zLCBlbnN1cmUgeW91IG1vZGlmeSB0aGUgZmllbGRWYWxpZGF0aW9uIG9iamVjdCBhY2NvcmRpbmdseVxuICAgICAgY3VzdG9tLmFwcGx5KHNlbGYsIFtzZWxmLCBmaWVsZFZhbGlkYXRpb25dKVxuICAgIH1cbiAgfVxufVxuXG4vKlxuICogalF1ZXJ5IFBsdWdpblxuICovXG4kLmZuLnVpRm9ybVZhbGlkYXRpb24gPSBmdW5jdGlvbiAob3ApIHtcbiAgLy8gRmlyZSBhIGNvbW1hbmQgdG8gdGhlIEZvcm1WYWxpZGF0aW9uIG9iamVjdCwgZS5nLiAkKCdbZGF0YS1mb3JtdmFsaWRhdGlvbl0nKS51aUZvcm1WYWxpZGF0aW9uKCd2YWxpZGF0ZScsIHsuLn0pXG4gIGlmICh0eXBlb2Ygb3AgPT09ICdzdHJpbmcnICYmIC9edmFsaWRhdGV8dmFsaWRhdGVGaWVsZHxjbGVhcnxjbGVhckFsbCQvLnRlc3Qob3ApKSB7XG4gICAgLy8gR2V0IGZ1cnRoZXIgYWRkaXRpb25hbCBhcmd1bWVudHMgdG8gYXBwbHkgdG8gdGhlIG1hdGNoZWQgY29tbWFuZCBtZXRob2RcbiAgICB2YXIgYXJncyA9IEFycmF5LnByb3RvdHlwZS5zbGljZS5jYWxsKGFyZ3VtZW50cylcbiAgICBhcmdzLnNoaWZ0KClcblxuICAgIC8vIEZpcmUgY29tbWFuZCBvbiBlYWNoIHJldHVybmVkIGVsZW0gaW5zdGFuY2VcbiAgICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgICBpZiAoZWxlbS5Gb3JtVmFsaWRhdGlvbiAmJiB0eXBlb2YgZWxlbS5Gb3JtVmFsaWRhdGlvbltvcF0gPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgZWxlbS5Gb3JtVmFsaWRhdGlvbltvcF0uYXBwbHkoZWxlbS5Gb3JtVmFsaWRhdGlvbiwgYXJncylcbiAgICAgIH1cbiAgICB9KVxuXG4gIC8vIFNldCB1cCBhIG5ldyBGb3JtVmFsaWRhdGlvbiBpbnN0YW5jZSBwZXIgZWxlbSAoaWYgb25lIGRvZXNuJ3QgYWxyZWFkeSBleGlzdClcbiAgfSBlbHNlIHtcbiAgICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgICBpZiAoIWVsZW0uRm9ybVZhbGlkYXRpb24pIHtcbiAgICAgICAgbmV3IEZvcm1WYWxpZGF0aW9uKGVsZW0sIG9wKVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgJChlbGVtKS51aUZvcm1WYWxpZGF0aW9uKCd2YWxpZGF0ZScpXG4gICAgICB9XG4gICAgfSlcbiAgfVxufVxuXG4vKlxuICogalF1ZXJ5IEV2ZW50c1xuICovXG4kKGRvY3VtZW50KVxuICAvLyAtLSBJbnN0YXRpYXRlIGFueSBlbGVtZW50IHdpdGggW2RhdGEtZm9ybXZhbGlkYXRpb25dIG9uIHJlYWR5XG4gIC5vbigncmVhZHknLCBmdW5jdGlvbiAoKSB7XG4gICAgJCgnW2RhdGEtZm9ybXZhbGlkYXRpb25dJykudWlGb3JtVmFsaWRhdGlvbigpXG4gIH0pXG5cbiAgLy8gLS1cblxubW9kdWxlLmV4cG9ydHMgPSBGb3JtVmFsaWRhdGlvblxuIiwiLypcbiAqIFVuaWxlbmQgUGFzc3dvcmQgQ2hlY2tcbiAqL1xuXG4vLyBAVE9ETyBpbnRlZ3JhdGUgRGljdGlvbmFyeVxuXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbClcblxuLy8gQXV0b0NvbXBsZXRlIExhbmd1YWdlXG52YXIgRGljdGlvbmFyeSA9IHJlcXVpcmUoJ0RpY3Rpb25hcnknKVxudmFyIEVsZW1lbnRBdHRyc09iamVjdCA9IHJlcXVpcmUoJ0VsZW1lbnRBdHRyc09iamVjdCcpXG5cbmZ1bmN0aW9uIGVzY2FwZVF1b3RlcyAoaW5wdXQpIHtcbiAgcmV0dXJuIGlucHV0LnJlcGxhY2UoLycvZywgJyYjMzk7JykucmVwbGFjZSgvXCIvZywgJyYjMzQ7Jylcbn1cblxudmFyIFBhc3N3b3JkQ2hlY2sgPSBmdW5jdGlvbiAoaW5wdXQsIG9wdGlvbnMpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG4gIHNlbGYuJGlucHV0ID0gJChpbnB1dClcblxuICAvLyBFcnJvcjogaW52YWxpZCBlbGVtZW50XG4gIGlmIChzZWxmLiRpbnB1dC5sZW5ndGggPT09IDAgfHwgIXNlbGYuJGlucHV0LmlzKCdpbnB1dCwgdGV4dGFyZWEnKSkge1xuICAgIGNvbnNvbGUubG9nKCdQYXNzd29yZENoZWNrIEVycm9yOiBnaXZlbiBlbGVtZW50IGlzIG5vdCBhbiA8aW5wdXQ+JywgaW5wdXQpXG4gICAgcmV0dXJuXG4gIH1cblxuICAvLyBTZXR0aW5nc1xuICBzZWxmLnNldHRpbmdzID0gJC5leHRlbmQoXG4gIC8vIERlZmF1bHQgc2V0dGluZ3NcbiAge1xuICAgIC8vIEV4dHJhIGV2YWx1YXRpb24gcnVsZXMgdG8gY2hlY2tcbiAgICBldmFsdWF0aW9uUnVsZXM6IFtdLFxuXG4gICAgLy8gTWF4IGFtb3VudCB0byBzY29yZSB3aXRoIGFsbCB0aGUgcnVsZXNcbiAgICBtYXhTY29yZTogMTUsXG5cbiAgICAvLyBUaGUgbWluaW11bSBsZW5ndGggb2YgdGhlIHBhc3N3b3JkXG4gICAgbWluTGVuZ3RoOiA4LFxuXG4gICAgLy8gVGhlIFVJIGVsZW1lbnRzIHRvIG91dHB1dCBtZXNzYWdlcyBvciBvdGhlciBmZWVkYmFjayB0byAoY2FuIGJlIHtTdHJpbmd9IHNlbGVjdG9ycywge0hUTUxFbGVtZW50fXMgb3Ige2pRdWVyeU9iamVjdH1zIGFzIHdlbGwgYXMge1N0cmluZ30gSFRNTCBjb2RlKVxuICAgIGxldmVsRWxlbTogJzxkaXYgY2xhc3M9XCJ1aS1wYXNzd29yZGNoZWNrLWxldmVsXCI+PGRpdiBjbGFzcz1cInVpLXBhc3N3b3JkY2hlY2stbGV2ZWwtYmFyXCI+PC9kaXY+PC9kaXY+JyxcbiAgICBpbmZvRWxlbTogJzxkaXYgY2xhc3M9XCJ1aS1wYXNzd29yZGNoZWNrLWluZm9cIj48L2Rpdj4nLFxuXG4gICAgLy8gVGhlIGxldmVsIG9mIHNlY3VyaXR5IHRoZVxuICAgIGxldmVsczogW3tcbiAgICAgIG5hbWU6ICd3ZWFrLXZlcnknLFxuICAgICAgbGFiZWw6ICdWZXJ5IHdlYWsnXG4gICAgfSx7XG4gICAgICBuYW1lOiAnd2VhaycsXG4gICAgICBsYWJlbDogJ1dlYWsnXG4gICAgfSx7XG4gICAgICBuYW1lOiAnbWVkaXVtJyxcbiAgICAgIGxhYmVsOiAnTWVkaXVtJ1xuICAgIH0se1xuICAgICAgbmFtZTogJ3N0cm9uZycsXG4gICAgICBsYWJlbDogJ1N0cm9uZydcbiAgICB9LHtcbiAgICAgIG5hbWU6ICdzdHJvbmctdmVyeScsXG4gICAgICBsYWJlbDogJ1Zlcnkgc3Ryb25nJ1xuICAgIH1dLFxuXG4gICAgLy8gRXZlbnQgdG8gZmlyZSB3aGVuIGFuIGV2YWx1YXRpb24gaGFzIGNvbXBsZXRlZFxuICAgIC8vIEJ5IGRlZmF1bHQgdGhpcyB1cGRhdGVzIHRoZSBVSS4gSWYgeW91IGFyZSB1c2luZyBjdXN0b20gVUkgZWxlbWVudHMgdG8gb3V0cHV0IHRvIHlvdSBtYXkgbmVlZCB0byBjcmVhdGUgeW91ciBvd24gdmVyc2lvbiBvZiB0aGlzXG4gICAgb25ldmFsdWF0aW9uOiBmdW5jdGlvbiAoZXZhbHVhdGlvbikge1xuICAgICAgLy8gU2V0IGxldmVsXG4gICAgICB0aGlzLiRsZXZlbC5maW5kKCcudWktcGFzc3dvcmRjaGVjay1sZXZlbC1iYXInKS5jc3MoJ3dpZHRoJywgZXZhbHVhdGlvbi5sZXZlbEFtb3VudCAqIDEwMCArICclJylcbiAgICAgIHRoaXMuJGVsZW0uYWRkQ2xhc3MoJ3VpLXBhc3N3b3JkY2hlY2stY2hlY2tlZCB1aS1wYXNzd29yZGNoZWNrLWxldmVsLScgKyBldmFsdWF0aW9uLmxldmVsLm5hbWUpXG5cbiAgICAgIC8vIFNob3cgZGVzY3JpcHRpb24gb2YgZXZhbHVhdGlvblxuICAgICAgdmFyIGluZm9MYWJlbCA9IGV2YWx1YXRpb24ubGV2ZWwubGFiZWxcbiAgICAgIHZhciBpbmZvTW9yZUh0bWwgPSAnJ1xuICAgICAgaWYgKGV2YWx1YXRpb24uaW5mby5sZW5ndGggPiAwKSB7XG4gICAgICAgIGluZm9MYWJlbCArPSAnIDxhIGhyZWY9XCJqYXZhc2NyaXB0OjtcIj48c3BhbiBjbGFzcz1cImljb24gZmEtcXVlc3Rpb24tY2lyY2xlXCI+PC9zcGFuPjwvYT4nXG4gICAgICAgIGluZm9Nb3JlSHRtbCArPSAnPHVsIGNsYXNzPVwidWktcGFzc3dvcmRjaGVjay1tZXNzYWdlc1wiPidcbiAgICAgICAgZm9yICh2YXIgaSA9IDA7IGkgPCBldmFsdWF0aW9uLmluZm8ubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICB2YXIgaGVscFRleHQgPSAodHlwZW9mIGV2YWx1YXRpb24uaW5mb1tpXS5oZWxwICE9PSAndW5kZWZpbmVkJyA/IGV2YWx1YXRpb24uaW5mb1tpXS5oZWxwIDogJycpXG4gICAgICAgICAgdmFyIGRlc2NyaXB0aW9uVGV4dCA9ICh0eXBlb2YgZXZhbHVhdGlvbi5pbmZvW2ldLmRlc2NyaXB0aW9uICE9PSAndW5kZWZpbmVkJyA/ICc8c3Ryb25nPicgKyBldmFsdWF0aW9uLmluZm9baV0uZGVzY3JpcHRpb24gKyAnPC9zdHJvbmc+PGJyLz4nIDogJycpXG4gICAgICAgICAgaWYgKGRlc2NyaXB0aW9uVGV4dCB8fCBoZWxwVGV4dCkgaW5mb01vcmVIdG1sICs9ICc8bGk+JyArIGRlc2NyaXB0aW9uVGV4dCArIGhlbHBUZXh0ICsgJzwvbGk+J1xuICAgICAgICB9XG4gICAgICAgIGluZm9Nb3JlSHRtbCArPSAnPC91bD4nXG4gICAgICB9XG4gICAgICB0aGlzLiRpbmZvLmh0bWwoJzxkaXYgY2xhc3M9XCJ1aS1wYXNzd29yZGNoZWNrLWxldmVsLWxhYmVsXCI+JyArIGluZm9MYWJlbCArICc8L2Rpdj4nICsgaW5mb01vcmVIdG1sKVxuICAgIH1cbiAgfSxcbiAgLy8gR2V0IGFueSBzZXR0aW5ncy9vcHRpb25zIGZyb20gdGhlIGVsZW1lbnQgaXRzZWxmXG4gIEVsZW1lbnRBdHRyc09iamVjdChpbnB1dCwge1xuICAgIG1pbkxlbmd0aDogJ2RhdGEtcGFzc3dvcmRjaGVjay1taW5sZW5ndGgnLFxuICAgIGxldmVsRWxlbTogJ2RhdGEtcGFzc3dvcmRjaGVjay1sZXZlbGVsZW0nLFxuICAgIGluZm9FbGVtOiAnZGF0YS1wYXNzd29yZGNoZWNrLWluZm9lbGVtJ1xuICB9KSxcbiAgLy8gT3ZlcnJpZGUgd2l0aCBmdW5jdGlvbiBjYWxsJ3Mgb3B0aW9uc1xuICBvcHRpb25zKVxuXG4gIC8vIFVJIGVsZW1lbnRzXG4gIHNlbGYuJGVsZW0gPSBzZWxmLiRpbnB1dC5wYXJlbnRzKCcudWktcGFzc3dvcmRjaGVjaycpXG4gIHNlbGYuJGxldmVsID0gJChzZWxmLnNldHRpbmdzLmxldmVsKVxuICBzZWxmLiRpbmZvID0gJChzZWxmLnNldHRpbmdzLmluZm8pXG5cbiAgLy8gU2V0dXAgdGhlIFVJXG4gIC8vIE5vIG1haW4gZWxlbWVudD8gV3JhcCB0aGUgaW5wdXQgd2l0aCBhIG1haW4gZWxlbWVudFxuICBpZiAoc2VsZi4kZWxlbS5sZW5ndGggPT09IDApIHtcbiAgICBzZWxmLiRpbnB1dC53cmFwKCc8ZGl2IGNsYXNzPVwidWktcGFzc3dvcmRjaGVja1wiPjwvZGl2PicpXG4gICAgc2VsZi4kZWxlbSA9IHNlbGYuJGlucHV0LnBhcmVudHMoJy51aS1wYXNzd29yZGNoZWNrJylcbiAgfVxuICAvLyBFbnN1cmUgaW5wdXQgaGFzIHJpZ2h0IGNsYXNzZXMgYW5kIGl0J3Mgb3duIHdyYXBcbiAgc2VsZi4kaW5wdXQuYWRkQ2xhc3MoJ3VpLXBhc3N3b3JkY2hlY2staW5wdXQnKVxuICBpZiAoc2VsZi4kaW5wdXQucGFyZW50cygnLnVpLXBhc3N3b3JkY2hlY2staW5wdXQtd3JhcCcpLmxlbmd0aCA9PT0gMCkge1xuICAgIHNlbGYuJGlucHV0LndyYXAoJzxkaXYgY2xhc3M9XCJ1aS1wYXNzd29yZGNoZWNrLWlucHV0LXdyYXBcIj48L2Rpdj4nKVxuICB9XG4gIHNlbGYuJGxldmVsID0gJChzZWxmLnNldHRpbmdzLmxldmVsRWxlbSlcbiAgc2VsZi4kaW5mbyA9ICQoc2VsZi5zZXR0aW5ncy5pbmZvRWxlbSlcblxuICAvLyBJZiBlbGVtZW50cyBhcmVuJ3QgYWxyZWFkeSBleGlzdGluZyBzZWxlY3RvcnMsIEhUTUxFbGVtZW50cyBvciBvYmplY3RzLCBhZGQgdGhlbSB0byB0aGUgRE9NIGluIHRoZSBjb3JyZWN0IHBsYWNlc1xuICBpZiAoISQuY29udGFpbnMoZG9jdW1lbnQsIHNlbGYuJGxldmVsWzBdKSkgc2VsZi4kaW5wdXQuYWZ0ZXIoc2VsZi4kbGV2ZWwpXG4gIGlmICghJC5jb250YWlucyhkb2N1bWVudCwgc2VsZi4kaW5mb1swXSkpIHNlbGYuJGVsZW0uYXBwZW5kKHNlbGYuJGluZm8pXG5cbiAgLy8gQGRlYnVnXG4gIC8vIGNvbnNvbGUubG9nKHtcbiAgLy8gICBlbGVtOiBzZWxmLiRlbGVtLFxuICAvLyAgIGlucHV0OiBzZWxmLiRpbnB1dCxcbiAgLy8gICBsZXZlbDogc2VsZi4kbGV2ZWwsXG4gIC8vICAgaW5mbzogc2VsZi4kaW5mb1xuICAvLyB9KVxuXG4gIC8vIFJlc2V0IHRoZSBVSVxuICBzZWxmLnJlc2V0ID0gZnVuY3Rpb24gKHNvZnQpIHtcbiAgICB2YXIgcmVtb3ZlQ2xhc3NlcyA9ICQubWFwKHNlbGYuc2V0dGluZ3MubGV2ZWxzLCBmdW5jdGlvbiAobGV2ZWwpIHtcbiAgICAgIHJldHVybiAndWktcGFzc3dvcmRjaGVjay1sZXZlbC0nICsgbGV2ZWwubmFtZVxuICAgIH0pLmpvaW4oJyAnKVxuICAgIHNlbGYuJGVsZW0ucmVtb3ZlQ2xhc3MocmVtb3ZlQ2xhc3NlcyArICcgdWktcGFzc3dvcmRjaGVjay1jaGVja2VkJylcbiAgICBzZWxmLiRsZXZlbC5maW5kKCcudWktcGFzc3dvcmRjaGVjay1sZXZlbC1iYXInKS5jc3MoJ3dpZHRoJywgMClcbiAgICBzZWxmLiRpbmZvLmh0bWwoJycpXG5cbiAgICAvLyBTb2Z0IHJlc2V0XG4gICAgaWYgKCFzb2Z0KSB7XG4gICAgICBzZWxmLiRpbnB1dC52YWwoJycpXG4gICAgfVxuXG4gICAgLy8gVHJpZ2dlciBlbGVtZW50IGV2ZW50IGluIGNhc2UgYW55dGhpbmcgZWxzZSB3YW50cyB0byBob29rXG4gICAgc2VsZi4kaW5wdXQudHJpZ2dlcignUGFzc3dvcmRDaGVjazpyZXNldHRlZCcsIFtzZWxmLCBzb2Z0XSlcbiAgfVxuXG4gIC8vIEV2YWx1YXRlIGFuIGlucHV0IHZhbHVlIHRvIHNlZSBob3cgc2VjdXJlIGl0IGlzXG4gIHNlbGYuZXZhbHVhdGUgPSBmdW5jdGlvbiAoaW5wdXQpIHtcbiAgICBpZiAodHlwZW9mIGlucHV0ID09PSAndW5kZWZpbmVkJykgaW5wdXQgPSBzZWxmLiRpbnB1dC52YWwoKVxuICAgIGlmICh0eXBlb2YgaW5wdXQgPT09ICd1bmRlZmluZWQnKSByZXR1cm4gZmFsc2VcblxuICAgIHZhciBjb21wbGV4aXR5ID0gMFxuICAgIHZhciBzY29yZSA9IDBcbiAgICB2YXIgbGV2ZWwgPSAnJ1xuICAgIHZhciBsZXZlbEFtb3VudCA9IDBcbiAgICB2YXIgaW5mbyA9IFtdXG4gICAgdmFyIGV2YWx1YXRpb24gPSB7fVxuXG4gICAgLy8gVGhlIGV2YWx1YXRpb24gcnVsZXNcbiAgICB2YXIgZXZhbHVhdGlvblJ1bGVzID0gW3tcbiAgICAgIHJlOiAvW2Etel0rLyxcbiAgICAgIGFtb3VudDogMVxuICAgIH0se1xuICAgICAgcmU6IC9bQS1aXSsvLFxuICAgICAgYW1vdW50OiAxXG4gICAgfSx7XG4gICAgICByZTogL1swLTldKy8sXG4gICAgICBhbW91bnQ6IDFcbiAgICB9LHtcbiAgICAgIHJlOiAvW1xcdTIwMDAtXFx1MjA2RlxcdTJFMDAtXFx1MkU3RlxcXFwnIVwiIyQlJigpKissXFwtLlxcLzo7PD0+P0BcXFtcXF1eX2B7fH1+XSsvLFxuICAgICAgYW1vdW50OiAxXG4gICAgfSx7XG4gICAgICByZTogL3BbYTRdW3M1XSsoPzp3W28wXStyZCk/L2ksXG4gICAgICBhbW91bnQ6IC0xLFxuICAgICAgZGVzY3JpcHRpb246ICdWYXJpYXRpb25zIG9uIHRoZSB3b3JkIFwicGFzc3dvcmRcIicsXG4gICAgICBoZWxwOiAnQXZvaWQgdXNpbmcgdGhlIHdvcmQgXCJwYXNzd29yZFwiIG9yIGFueSBvdGhlciB2YXJpYXRpb24sIGUuZy4gXCJQNDU1dzByRFwiJ1xuICAgIH0se1xuICAgICAgcmU6IC9hc2RmfHF3ZXJ8enhjdnxnaGprfHR5aXV8amtsO3xubSwufHVpb3AvaSxcbiAgICAgIGFtb3VudDogLTEsXG4gICAgICBkZXNjcmlwdGlvbjogJ0NvbWJpbmF0aW9uIG1hdGNoZXMgY29tbW9uIGtleWJvYXJkIGxheW91dHMnLFxuICAgICAgaGVscDogJ0F2b2lkIHVzaW5nIGNvbW1vbiBrZXlib2FyZCBsYXlvdXQgY29tYmluYXRpb25zJ1xuICAgIH0se1xuICAgICAgcmU6IC8oW2EtejAtOV0pXFwxezIsfS9pLFxuICAgICAgYW1vdW50OiAtMSxcbiAgICAgIGRlc2NyaXB0aW9uOiAnUmVwZWF0ZWQgc2FtZSBjaGFyYWN0ZXInLFxuICAgICAgaGVscDogJ0F2b2lkIHJlcGVhdGluZyB0aGUgc2FtZSBjaGFyYWN0ZXIuIEFkZCBpbiBtb3JlIHZhcmlhdGlvbidcbiAgICB9LHtcbiAgICAgIHJlOiAvMTIzKD86NDU2Nzg5fDQ1Njc4fDQ1Njd8NDU2fDQ1fDQpPy8sXG4gICAgICBhbW91bnQ6IC0xLFxuICAgICAgZGVzY3JpcHRpb246ICdJbmNyZW1lbnRpbmcgbnVtYmVyIHNlcXVlbmNlJyxcbiAgICAgIGhlbHA6ICdBdm9pZCB1c2luZyBpbmNyZW1lbnRpbmcgbnVtYmVycydcbiAgICB9LHtcbiAgICAgIHJlOiAvYWJjfHh5ei9pLFxuICAgICAgYW1vdW50OiAtMSxcbiAgICAgIGRlc2NyaXB0aW9uOiAnQ29tbW9uIGFscGhhYmV0IHNlcXVlbmNlcycsXG4gICAgICBoZWxwOiAnQXZvaWQgdXNpbmcgY29tYmluYXRpb25zIGxpa2UgXCJhYmNcIiBhbmQgXCJ4eXpcIidcbiAgICB9XVxuICAgIGlmIChzZWxmLnNldHRpbmdzLmV2YWx1YXRpb25SdWxlcyBpbnN0YW5jZW9mIEFycmF5ICYmIHNlbGYuc2V0dGluZ3MuZXZhbHVhdGlvblJ1bGVzLmxlbmd0aCA+IDApIHtcbiAgICAgIGV2YWx1YXRpb25SdWxlcyArPSBzZWxmLnNldHRpbmdzLmV2YWx1YXRpb25SdWxlc1xuICAgIH1cblxuICAgIC8vIEV2YWx1YXRlIHRoZSBzdHJpbmcgYmFzZWQgb24gdGhlIG1pbkxlbmd0aFxuICAgIHZhciBpbnB1dExlbmd0aERpZmYgPSBpbnB1dC5sZW5ndGggLSBzZWxmLnNldHRpbmdzLm1pbkxlbmd0aFxuICAgIGlmIChpbnB1dC5sZW5ndGggPCBzZWxmLnNldHRpbmdzLm1pbkxlbmd0aCkge1xuICAgICAgc2NvcmUgLT0gMVxuICAgICAgaW5mby5wdXNoKHtcbiAgICAgICAgZGVzY3JpcHRpb246ICdQYXNzd29yZCBpcyB0b28gc2hvcnQnLFxuICAgICAgICBoZWxwOiAnQWRkIGV4dHJhIHdvcmRzIG9yIGNoYXJhY3RlcnMgdG8gbGVuZ3RoZW4geW91ciBwYXNzd29yZCdcbiAgICAgIH0pXG4gICAgfSBlbHNlIHtcbiAgICAgIHNjb3JlICs9IDFcbiAgICB9XG4gICAgY29tcGxleGl0eSArPSAoaW5wdXRMZW5ndGhEaWZmID4gMCA/IE1hdGguZmxvb3IoaW5wdXRMZW5ndGhEaWZmIC8gOCkgOiAwKVxuXG4gICAgLy8gRXZhbHVhdGUgdGhlIHN0cmluZyBiYXNlZCBvbiB0aGUgcnVsZXNcbiAgICBmb3IgKHZhciBpID0gMDsgaSA8IGV2YWx1YXRpb25SdWxlcy5sZW5ndGg7IGkrKykge1xuICAgICAgdmFyIHJ1bGUgPSBldmFsdWF0aW9uUnVsZXNbaV1cbiAgICAgIHZhciBydWxlSW5mbyA9IHt9XG4gICAgICBpZiAocnVsZS5oYXNPd25Qcm9wZXJ0eSgncmUnKSAmJiBydWxlLnJlIGluc3RhbmNlb2YgUmVnRXhwKSB7XG4gICAgICAgIC8vIFBvc2l0aXZlIG1hdGNoXG4gICAgICAgIGlmIChydWxlLnJlLnRlc3QoaW5wdXQpKSB7XG4gICAgICAgICAgc2NvcmUgKz0gcnVsZS5hbW91bnRcbiAgICAgICAgICBjb21wbGV4aXR5ICs9IDFcbiAgICAgICAgICBpZiAocnVsZS5oYXNPd25Qcm9wZXJ0eSgnZGVzY3JpcHRpb24nKSkgcnVsZUluZm8uZGVzY3JpcHRpb24gPSBydWxlLmRlc2NyaXB0aW9uXG4gICAgICAgICAgaWYgKHJ1bGUuaGFzT3duUHJvcGVydHkoJ2hlbHAnKSkgcnVsZUluZm8uaGVscCA9IHJ1bGUuaGVscFxuICAgICAgICAgIGlmIChydWxlLmhhc093blByb3BlcnR5KCdkZXNjcmlwdGlvbicpIHx8IHJ1bGUuaGFzT3duUHJvcGVydHkoJ2hlbHAnKSkgaW5mby5wdXNoKHJ1bGVJbmZvKVxuICAgICAgICB9XG4gICAgICB9XG4gICAgfVxuXG4gICAgLy8gRXh0cmEgY2hlY2tzXG4gICAgaWYgKGNvbXBsZXhpdHkgPCAzKSB7XG4gICAgICBpbmZvLnB1c2goe1xuICAgICAgICBkZXNjcmlwdGlvbjogJ1Bhc3N3b3JkIGlzIHBvdGVudGlhbGx5IHRvbyBzaW1wbGUnLFxuICAgICAgICBoZWxwOiAnVXNlIGEgY29tYmluYXRpb24gb2YgdXBwZXItY2FzZSwgbG93ZXItY2FzZSwgbnVtYmVycyBhbmQgcHVuY3R1YXRpb24gY2hhcmFjdGVycydcbiAgICAgIH0pXG4gICAgfVxuXG4gICAgLy8gVHVybiBzY29yZSBpbnRvIGEgbGV2ZWxcbiAgICBsZXZlbEFtb3VudCA9IChzY29yZSAqIGNvbXBsZXhpdHkpIC8gc2VsZi5zZXR0aW5ncy5tYXhTY29yZVxuICAgIGlmIChzY29yZSA8IDApIGxldmVsQW1vdW50ID0gMCAvLyBDYXAgbWluaW11bVxuICAgIGlmIChsZXZlbEFtb3VudCA+IDEpIGxldmVsQW1vdW50ID0gMSAvLyBDYXAgbWF4aW11bVxuICAgIGxldmVsID0gc2VsZi5zZXR0aW5ncy5sZXZlbHNbTWF0aC5mbG9vcihsZXZlbEFtb3VudCAqIChzZWxmLnNldHRpbmdzLmxldmVscy5sZW5ndGggLSAxKSldXG5cbiAgICBldmFsdWF0aW9uID0ge1xuICAgICAgc2NvcmU6IHNjb3JlLFxuICAgICAgY29tcGxleGl0eTogY29tcGxleGl0eSxcbiAgICAgIGxldmVsQW1vdW50OiBsZXZlbEFtb3VudCxcbiAgICAgIGxldmVsOiBsZXZlbCxcbiAgICAgIGluZm86IGluZm9cbiAgICB9XG5cbiAgICAvLyBGaXJlIHRoZSBvbmV2YWx1YXRpb24gZXZlbnQgdG8gdXBkYXRlIHRoZSBVSVxuICAgIHNlbGYucmVzZXQoMSlcbiAgICBpZiAodHlwZW9mIHNlbGYuc2V0dGluZ3Mub25ldmFsdWF0aW9uID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICBzZWxmLnNldHRpbmdzLm9uZXZhbHVhdGlvbi5hcHBseShzZWxmLCBbZXZhbHVhdGlvbl0pXG4gICAgfVxuXG4gICAgLy8gVHJpZ2dlciBlbGVtZW50IGV2ZW50IGluIGNhc2UgYW55dGhpbmcgZWxzZSB3YW50cyB0byBob29rXG4gICAgc2VsZi4kaW5wdXQudHJpZ2dlcignUGFzc3dvcmRDaGVjazpldmFsdWF0aW9uJywgW3NlbGYsIGV2YWx1YXRpb25dKVxuXG4gICAgLy8gQGRlYnVnXG4gICAgLy8gY29uc29sZS5sb2coJ1Bhc3N3b3JkQ2hlY2suZXZhbHVhdGUnLCBldmFsdWF0aW9uKVxuXG4gICAgcmV0dXJuIGV2YWx1YXRpb25cbiAgfVxuXG4gIC8vIEhvb2sgZXZlbnRzIHRvIHRoZSBlbGVtZW50XG4gIHNlbGYuJGlucHV0Lm9uKCdrZXl1cCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIC8vIEV2YWx1YXRlIHRoZSBlbGVtZW50J3MgaW5wdXRcbiAgICBpZiAoJCh0aGlzKS52YWwoKS5sZW5ndGggPiAwKSB7XG4gICAgICBzZWxmLmV2YWx1YXRlKClcblxuICAgIC8vIFJlc2V0IHRoZSBVSVxuICAgIH0gZWxzZSBpZiAoJCh0aGlzKS52YWwoKS5sZW5ndGggPT09IDApIHtcbiAgICAgIHNlbGYucmVzZXQoKVxuICAgIH1cbiAgfSlcblxuICAvLyBTaG93L2hpZGUgdGhlIGluZm9cbiAgc2VsZi4kaW5mby5vbignY2xpY2snLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpXG4gICAgc2VsZi4kZWxlbS50b2dnbGVDbGFzcygndWktcGFzc3dvcmRjaGVjay1pbmZvLW9wZW4nKVxuICB9KVxufVxuXG4vKlxuICogalF1ZXJ5IFBsdWdpblxuICovXG4kLmZuLnVpUGFzc3dvcmRDaGVjayA9IGZ1bmN0aW9uIChvcHRpb25zKSB7XG4gIHJldHVybiB0aGlzLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICBuZXcgUGFzc3dvcmRDaGVjayhlbGVtLCBvcHRpb25zKVxuICB9KVxufVxuXG4vKlxuICogalF1ZXJ5IEV2ZW50c1xuICovXG4kKGRvY3VtZW50KVxuICAvLyBBdXRvLWFzc2lnbiBmdW5jdGlvbmFsaXR5IHRvIGVsZW1lbnRzIHdpdGggW2RhdGEtcGFzc3dvcmRjaGVja10gYXR0cmlidXRlXG4gIC5vbigncmVhZHknLCBmdW5jdGlvbiAoKSB7XG4gICAgJCgnW2RhdGEtcGFzc3dvcmRjaGVja10nKS51aVBhc3N3b3JkQ2hlY2soKVxuICB9KVxuXG5tb2R1bGUuZXhwb3J0cyA9IFBhc3N3b3JkQ2hlY2tcbiIsIi8qXG4gKiBVbmlsZW5kIFNvcnRhYmxlXG4gKiBSZS1vcmRlciBlbGVtZW50cyB3aXRoaW4gYW5vdGhlciBlbGVtZW50IGRlcGVuZGluZyBvbiBhIHZhbHVlXG4gKi9cblxuLy8gRGVwZW5kZW5jaWVzXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbClcblxuLy8gQ29udmVydCBhbiBpbnB1dCB2YWx1ZSAobW9zdCBsaWtlbHkgYSBzdHJpbmcpIGludG8gYSBwcmltaXRpdmUsIGUuZy4gbnVtYmVyLCBib29sZWFuLCBldGMuXG5mdW5jdGlvbiBjb252ZXJ0VG9QcmltaXRpdmUgKGlucHV0KSB7XG4gIC8vIE5vbi1zdHJpbmc/IEp1c3QgcmV0dXJuIGl0IHN0cmFpZ2h0IGF3YXlcbiAgaWYgKHR5cGVvZiBpbnB1dCAhPT0gJ3N0cmluZycpIHJldHVybiBpbnB1dFxuXG4gIC8vIFRyaW0gYW55IHdoaXRlc3BhY2VcbiAgaW5wdXQgPSAoaW5wdXQgKyAnJykudHJpbSgpXG5cbiAgLy8gTnVtYmVyXG4gIGlmICgvXlxcLT8oPzpcXGQqW1xcLlxcLF0pKlxcZCooPzpbZUVdKD86XFwtP1xcZCspPyk/JC8udGVzdChpbnB1dCkpIHtcbiAgICByZXR1cm4gcGFyc2VGbG9hdChpbnB1dClcbiAgfVxuXG4gIC8vIEJvb2xlYW46IHRydWVcbiAgaWYgKC9edHJ1ZXwxJC8udGVzdChpbnB1dCkpIHtcbiAgICByZXR1cm4gdHJ1ZVxuXG4gIC8vIE5hTlxuICB9IGVsc2UgaWYgKC9eTmFOJC8udGVzdChpbnB1dCkpIHtcbiAgICByZXR1cm4gTmFOXG5cbiAgLy8gdW5kZWZpbmVkXG4gIH0gZWxzZSBpZiAoL151bmRlZmluZWQkLy50ZXN0KGlucHV0KSkge1xuICAgIHJldHVybiB1bmRlZmluZWRcblxuICAvLyBudWxsXG4gIH0gZWxzZSBpZiAoL15udWxsJC8udGVzdChpbnB1dCkpIHtcbiAgICByZXR1cm4gbnVsbFxuXG4gIC8vIEJvb2xlYW46IGZhbHNlXG4gIH0gZWxzZSBpZiAoL15mYWxzZXwwJC8udGVzdChpbnB1dCkgfHwgaW5wdXQgPT09ICcnKSB7XG4gICAgcmV0dXJuIGZhbHNlXG4gIH1cblxuICAvLyBEZWZhdWx0IHRvIHN0cmluZ1xuICByZXR1cm4gaW5wdXRcbn1cblxuLypcbiAqIFNvcnRhYmxlIGNsYXNzXG4gKlxuICogQGNsYXNzXG4gKiBAcGFyYW0ge01peGVkfSBlbGVtIENhbiBiZSBhIHtTdHJpbmd9IHNlbGVjdG9yLCB7SFRNTEVsZW1lbnR9IG9yIHtqUXVlcnlFbGVtZW50fVxuICogQHBhcmFtIHtPYmplY3R9IG9wdGlvbnMgQW4gb2JqZWN0IGNvbnRhaW5pbmcgY29uZmlndXJhYmxlIHNldHRpbmdzIGZvciB0aGUgc29ydGFibGUgZWxlbWVudFxuICogQHJldHVybnMge1NvcnRhYmxlfVxuICovXG52YXIgU29ydGFibGUgPSBmdW5jdGlvbiAoZWxlbSwgb3B0aW9ucykge1xuICB2YXIgc2VsZiA9IHRoaXNcblxuICAvLyBJbnZhbGlkIGVsZW1lbnRcbiAgaWYgKCQoZWxlbSkubGVuZ3RoID09PSAwKSByZXR1cm5cblxuICAvLyBQcm9wZXJ0aWVzXG4gIHNlbGYuJGVsZW0gPSB1bmRlZmluZWRcbiAgc2VsZi4kY29sdW1ucyA9IHVuZGVmaW5lZFxuICBzZWxmLmNvbHVtbk5hbWVzID0gW11cbiAgc2VsZi4kY29udGVudCA9IHVuZGVmaW5lZFxuICBzZWxmLnNvcnRlZENvbHVtbiA9IGZhbHNlXG4gIHNlbGYuc29ydGVkRGlyZWN0aW9uID0gZmFsc2VcblxuICAvLyBTZXR1cCB0aGUgU29ydGFibGVcbiAgcmV0dXJuIHNlbGYuc2V0dXAoZWxlbSwgb3B0aW9ucylcbn1cblxuLypcbiAqIEdldCBkYXRhIGF0dHJpYnV0ZXMgZnJvbSBhbiBlbGVtZW50IChjb252ZXJ0cyBzdHJpbmcgdmFsdWVzIHRvIEpTIHByaW1pdGl2ZXMpXG4gKlxuICogQG1ldGhvZCBhdHRyc1RvT2JqZWN0XG4gKiBAcGFyYW0ge01peGVkfSBlbGVtIENhbiBiZSBhIHtTdHJpbmd9IHNlbGVjdG9yLCB7SFRNTEVsZW1lbnR9IG9yIHtqUXVlcnlFbGVtZW50fVxuICogQHBhcmFtIHtBcnJheX0gYXR0cnMgQW4gYXJyYXkgb2Yge1N0cmluZ3N9IHdoaWNoIGNvbnRhaW4gbmFtZXMgb2YgYXR0cmlidXRlcyB0byByZXRyaWV2ZSAodGhlc2UgYXR0cmlidXRlcyB3aWxsIGFscmVhZHkgYmUgbmFtZXNwYWNlZCB0byBmZXRjaCBgZGF0YS1zb3J0YWJsZS17bmFtZX1gKVxuICogQHJldHVybnMge09iamVjdH1cbiAqL1xuU29ydGFibGUucHJvdG90eXBlLmF0dHJzVG9PYmplY3QgPSBmdW5jdGlvbiAoZWxlbSwgYXR0cnMpIHtcbiAgdmFyICRlbGVtID0gJChlbGVtKS5maXJzdCgpXG4gIHZhciBzZWxmID0gdGhpc1xuICB2YXIgb3V0cHV0ID0ge31cblxuICBpZiAoJGVsZW0ubGVuZ3RoID09PSAwIHx8ICFhdHRycykgcmV0dXJuIG91dHB1dFxuXG4gIGZvciAodmFyIGkgaW4gYXR0cnMpIHtcbiAgICB2YXIgYXR0clZhbHVlID0gY29udmVydFRvUHJpbWl0aXZlKCRlbGVtLmF0dHIoJ2RhdGEtc29ydGFibGUtJyArIGF0dHJzW2ldKSlcbiAgICBvdXRwdXRbYXR0cnNbaV1dID0gYXR0clZhbHVlXG4gIH1cblxuICByZXR1cm4gb3V0cHV0XG59XG5cbi8qXG4gKiBTZXR1cCB0aGUgZWxlbWVudCBhbmQgcHJvcGVydGllc1xuICpcbiAqIEBtZXRob2Qgc2V0dXBcbiAqIEBwYXJhbSB7TWl4ZWR9IGVsZW0gQ2FuIGJlIGEge1N0cmluZ30gc2VsZWN0b3IsIHtIVE1MRWxlbWVudH0gb3Ige2pRdWVyeUVsZW1lbnR9XG4gKiBAcGFyYW0ge09iamVjdH0gb3B0aW9ucyBBbiBvYmplY3QgY29udGFpbmluZyBjb25maWd1cmFibGUgc2V0dGluZ3MgZm9yIHRoZSBzb3J0YWJsZSBlbGVtZW50XG4gKiBAcmV0dXJucyB7U29ydGFibGV9XG4gKi9cblNvcnRhYmxlLnByb3RvdHlwZS5zZXR1cCA9IGZ1bmN0aW9uIChlbGVtLCBvcHRpb25zKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuXG4gIC8vIEludmFsaWQgZWxlbWVudFxuICBpZiAoJChlbGVtKS5sZW5ndGggPT09IDApIHJldHVyblxuXG4gIC8vIFVuaG9vayBhbnkgcHJldmlvdXMgZWxlbWVudHNcbiAgaWYgKHNlbGYuJGVsZW0gJiYgc2VsZi4kZWxlbS5sZW5ndGggPiAwKSB7XG4gICAgc2VsZi4kZWxlbS5yZW1vdmVDbGFzcygndWktc29ydGFibGUnKVxuICB9XG5cbiAgLy8gU2V0dXAgdGhlIHByb3BlcnRpZXNcbiAgc2VsZi4kZWxlbSA9ICQoZWxlbSkuZmlyc3QoKVxuICBzZWxmLiRjb2x1bW5zID0gdW5kZWZpbmVkXG4gIHNlbGYuY29sdW1uTmFtZXMgPSBbXVxuICBzZWxmLiRjb250ZW50ID0gdW5kZWZpbmVkXG4gIHNlbGYuc29ydGVkQ29sdW1uID0gZmFsc2VcbiAgc2VsZi5zb3J0ZWREaXJlY3Rpb24gPSBmYWxzZVxuXG4gIC8vIEdldCBhbnkgc2V0dGluZ3MgYXBwbGllZCB0byB0aGUgZWxlbWVudFxuICB2YXIgZWxlbVNldHRpbmdzID0gU29ydGFibGUucHJvdG90eXBlLmF0dHJzVG9PYmplY3QoZWxlbSwgWydjb2x1bW5zJywgJ2NvbnRlbnQnLCAnc2F2ZW9yaWdpbmFsb3JkZXInXSlcblxuICAvLyBTZXR0aW5ncyB3aXRoIGRlZmF1bHQgdmFsdWVzXG4gIHNlbGYuc2V0dGluZ3MgPSAkLmV4dGVuZCh7XG4gICAgLy8gVGhlIGNvbHVtbnMgdG8gc29ydCBieVxuICAgIGNvbHVtbnM6ICdbZGF0YS1zb3J0YWJsZS1ieV0nLFxuXG4gICAgLy8gVGhlIGNvbnRlbnQgdG8gc29ydFxuICAgIGNvbnRlbnQ6ICdbZGF0YS1zb3J0YWJsZS1jb250ZW50XScsXG5cbiAgICAvLyBTb3J0aW5nIGZ1bmN0aW9uXG4gICAgb25zb3J0Y29tcGFyZTogc2VsZi5zb3J0Q29tcGFyZSxcblxuICAgIC8vIFNhdmUgdGhlIG9yaWdpbmFsIG9yZGVyXG4gICAgc2F2ZW9yaWdpbmFsb3JkZXI6IHRydWVcbiAgfSwgZWxlbVNldHRpbmdzLCBvcHRpb25zKVxuXG4gIC8vIEdldC9zZXQgdGhlIGNvbHVtbnNcbiAgc2VsZi4kY29sdW1ucyA9ICQoc2VsZi5zZXR0aW5ncy5jb2x1bW5zKVxuICAvLyAtLSBOb3QgZm91bmQsIGxvb2sgd2l0aGluIGVsZW1lbnRcbiAgaWYgKHNlbGYuJGNvbHVtbnMubGVuZ3RoID09PSAwKSB7XG4gICAgc2VsZi4kY29sdW1ucyA9IHNlbGYuJGVsZW0uZmluZChzZWxmLnNldHRpbmdzLmNvbHVtbnMpXG4gICAgLy8gLS0gQWdhaW4sIG5vdCBmb3VuZC4gRXJyb3IhXG4gICAgaWYgKHNlbGYuJGNvbHVtbnMubGVuZ3RoID09PSAwKSB7XG4gICAgICB0aHJvdyBuZXcgRXJyb3IoJ1NvcnRhYmxlLnNldHVwIGVycm9yOiBubyBjb2x1bW5zIGRlZmluZWQuIE1ha2Ugc3VyZSB5b3Ugc2V0IGVhY2ggc29ydGFibGUgY29sdW1uIHdpdGggdGhlIEhUTUwgYXR0cmlidXRlIGBkYXRhLXNvcnRhYmxlLWJ5YCcpXG4gICAgfVxuICAgIC8vIFNldCB0aGUgY29sdW1uIGVsZW1lbnRzXG4gICAgc2VsZi4kY29sdW1ucyA9IHNlbGYuJGNvbHVtbnNcblxuICAgIC8vIEdldC9zZXQgdGhlIGNvbHVtbiBuYW1lc1xuICAgIHNlbGYuJGNvbHVtbnMuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgICAgdmFyIGNvbHVtbk5hbWUgPSAkKGVsZW0pLmF0dHIoJ2RhdGEtc29ydGFibGUtYnknKVxuICAgICAgaWYgKGNvbHVtbk5hbWUpIHNlbGYuY29sdW1uTmFtZXMucHVzaChjb2x1bW5OYW1lKVxuICAgIH0pXG4gIH1cblxuICAvLyBHZXQvc2V0IHRoZSBjb250ZW50XG4gIHNlbGYuJGNvbnRlbnQgPSAkKHNlbGYuc2V0dGluZ3MuY29udGVudClcbiAgLy8gLS0gTm90IGZvdW5kLCBsb29rIHdpdGhpbiBlbGVtZW50XG4gIGlmIChzZWxmLiRjb250ZW50Lmxlbmd0aCA9PT0gMCkge1xuICAgIHNlbGYuJGNvbnRlbnQgPSBzZWxmLiRlbGVtLmZpbmQoc2VsZi5zZXR0aW5ncy5jb250ZW50KVxuICAgIC8vIC0tIEFnYWluLCBub3QgZm91bmQuIEVycm9yIVxuICAgIGlmIChzZWxmLiRjb250ZW50Lmxlbmd0aCA9PT0gMCkge1xuICAgICAgdGhyb3cgbmV3IEVycm9yKCdTb3J0YWJsZS5zZXR1cCBlcnJvcjogbm8gY29udGVudCBkZWZpbmVkLiBNYWtlIHN1cmUgeW91IHNldCB0aGUgc29ydGFibGUgY29udGVudCB3aXRoIHRoZSBIVE1MIGF0dHJpYnV0ZSBgZGF0YS1zb3J0YWJsZS1jb250ZW50YCcpXG4gICAgfVxuXG4gICAgLy8gU2V0IHRoZSBjb250ZW50IGVsZW1lbnRcbiAgICBzZWxmLiRjb250ZW50ID0gc2VsZi4kY29udGVudC5maXJzdCgpXG4gIH1cblxuICAvLyBTYXZlIHRoZSBvcmlnaW5hbCBvcmRlclxuICBpZiAoc2VsZi5zZXR0aW5ncy5zYXZlb3JpZ2luYWxvcmRlcikge1xuICAgIHNlbGYuJGNvbnRlbnQuY2hpbGRyZW4oKS5lYWNoKGZ1bmN0aW9uIChpLCBpdGVtKSB7XG4gICAgICAkKGl0ZW0pLmF0dHIoJ2RhdGEtc29ydGFibGUtb3JpZ2luYWwtb3JkZXInLCBpKVxuICAgIH0pXG4gIH1cblxuICAvLyBUcmlnZ2VyIHNvcnRhYmxlIGVsZW1lbnQgd2hlbiByZWFkeVxuICBzZWxmLiRlbGVtWzBdLlNvcnRhYmxlID0gc2VsZlxuICBzZWxmLiRlbGVtLnRyaWdnZXIoJ3NvcnRhYmxlOnJlYWR5JylcbiAgcmV0dXJuIHNlbGZcbn1cblxuLypcbiAqIFNvcnQgdGhlIGVsZW1lbnQncyBjb250ZW50cyBieSBhIGNvbHVtbiBuYW1lIGFuZCBpbiBhIHBhcnRpY3VsYXIgZGlyZWN0aW9uXG4gKiAoaWYgbm8gZGlyZWN0aW9uIGdpdmVuLCBpdCB3aWxsIHRvZ2dsZSB0aGUgZGlyZWN0aW9uKVxuICpcbiAqIEBtZXRob2Qgc29ydFxuICogQHBhcmFtIHtTdHJpbmd9IGNvbHVtbk5hbWUgVGhlIG5hbWUgb2YgdGhlIGNvbHVtbiB0byBzb3J0IGJ5XG4gKiBAcGFyYW0ge1N0cmluZ30gZGlyZWN0aW9uIFRoZSBkaXJlY3Rpb24gdG8gc29ydDogYGFzY2AgfCBgZGVzY2BcbiAqIEByZXR1cm5zIHtTb3J0YWJsZX1cbiAqL1xuU29ydGFibGUucHJvdG90eXBlLnNvcnQgPSBmdW5jdGlvbiAoY29sdW1uTmFtZSwgZGlyZWN0aW9uKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuXG4gIC8vIERlZmF1bHRzXG4gIGNvbHVtbk5hbWUgPSBjb2x1bW5OYW1lIHx8ICdvcmlnaW5hbC1vcmRlcidcbiAgZGlyZWN0aW9uID0gZGlyZWN0aW9uIHx8ICdhc2MnXG5cbiAgLy8gVG9nZ2xlIHNvcnQgZGlyZWN0aW9uXG4gIGlmIChzZWxmLnNvcnRlZENvbHVtbiA9PT0gY29sdW1uTmFtZSB8fCBkaXJlY3Rpb24gPT09ICd0b2dnbGUnKSB7XG4gICAgZGlyZWN0aW9uID0gKHNlbGYuc29ydGVkRGlyZWN0aW9uID09PSAnYXNjJyA/ICdkZXNjJyA6ICdhc2MnKVxuICB9XG5cbiAgLy8gRG9uJ3QgbmVlZCB0byBzb3J0XG4gIGlmIChjb2x1bW5OYW1lID09PSBzZWxmLnNvcnRlZENvbHVtbiAmJiBkaXJlY3Rpb24gPT09IHNlbGYuc29ydGVkRGlyZWN0aW9uKSByZXR1cm4gc2VsZlxuXG4gIC8vIEdldCB0aGUgbmV3IGNvbHVtbiB0byBzb3J0IGFuZCBjb21wYXJlXG4gIHZhciAkc29ydENvbHVtbiA9IHNlbGYuJGNvbHVtbnMuZmlsdGVyKCdbZGF0YS1zb3J0YWJsZS1ieT1cIicgKyBjb2x1bW5OYW1lICsgJ1wiXScpXG5cbiAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdTb3J0YWJsZS5zb3J0OicsIGNvbHVtbk5hbWUsIGRpcmVjdGlvbilcblxuICAvLyBUcmlnZ2VyIGV2ZW50IGJlZm9yZSBzb3J0XG4gIHNlbGYuJGVsZW0udHJpZ2dlcignc29ydGFibGU6c29ydDpiZWZvcmUnLCBbY29sdW1uTmFtZSwgZGlyZWN0aW9uXSlcblxuICAvLyBEbyB0aGUgc29ydCBpbiB0aGUgVUlcbiAgc2VsZi4kY29udGVudC5jaGlsZHJlbigpLmRldGFjaCgpLnNvcnQoZnVuY3Rpb24gKGEsIGIpIHtcbiAgICByZXR1cm4gc2VsZi5zZXR0aW5ncy5vbnNvcnRjb21wYXJlLmFwcGx5KHNlbGYsIFthLCBiLCBjb2x1bW5OYW1lLCBkaXJlY3Rpb25dKVxuICB9KS5hcHBlbmRUbyhzZWxmLiRjb250ZW50KVxuXG4gIC8vIFVwZGF0ZSBTb3J0YWJsZSBwcm9wZXJ0aWVzXG4gIHNlbGYuc29ydGVkQ29sdW1uID0gY29sdW1uTmFtZVxuICBzZWxmLnNvcnRlZERpcmVjdGlvbiA9IGRpcmVjdGlvblxuXG4gIC8vIFVwZGF0ZSBVSVxuICBzZWxmLiRjb2x1bW5zLnJlbW92ZUNsYXNzKCd1aS1zb3J0YWJsZS1jdXJyZW50IHVpLXNvcnRhYmxlLWRpcmVjdGlvbi1hc2MgdWktc29ydGFibGUtZGlyZWN0aW9uLWRlc2MnKVxuICAkc29ydENvbHVtbi5hZGRDbGFzcygndWktc29ydGFibGUtY3VycmVudCB1aS1zb3J0YWJsZS1kaXJlY3Rpb24tJyArIGRpcmVjdGlvbilcblxuICAvLyBUcmlnZ2VyIGV2ZW50IGFmdGVyIHRoZSBzb3J0XG4gIHNlbGYuJGVsZW0udHJpZ2dlcignc29ydGFibGU6c29ydDphZnRlcicsIFtjb2x1bW5OYW1lLCBkaXJlY3Rpb25dKVxuXG4gIHJldHVybiBzZWxmXG59XG5cbi8qXG4gKiBHZW5lcmljIHNvcnRpbmcgY29tcGFyaXNvbiBmdW5jdGlvbiAoY29udmVydHMgdG8gZmxvYXRzIGFuZCBtYWtlcyBjb21wYXJpc29ucylcbiAqIFVzZXMgdGhlICQuc29ydCgpIG1ldGhvZCB3aGljaCBjb21wYXJlcyAyIGVsZW1lbnRzXG4gKlxuICogQG1ldGhvZCBzb3J0Q29tcGFyZVxuICogQHBhcmFtIHtNaXhlZH0gYSBUaGUgZmlyc3QgaXRlbSB0byBjb21wYXJlXG4gKiBAcGFyYW0ge01peGVkfSBiIFRoZSBzZWNvbmQgaXRlbSB0byBjb21wYXJlXG4gKiBAcGFyYW0ge1N0cmluZ30gY29sdW1uTmFtZSBUaGUgbmFtZSBvZiB0aGUgY29sdW1uIHRvIHNvcnQgYnlcbiAqIEBwYXJhbSB7U3RyaW5nfSBkaXJlY3Rpb24gVGhlIGRpcmVjdGlvbiB0byBzb3J0IChkZWZhdWx0IGlzIGBhc2NgKVxuICogQHJldHVybnMge051bWJlcn0gUmVwcmVzZW50cyBjb21wYXJpc29uOiAwIHwgMSB8IC0xXG4gKi9cblNvcnRhYmxlLnByb3RvdHlwZS5zb3J0Q29tcGFyZSA9IGZ1bmN0aW9uIChhLCBiLCBjb2x1bW5OYW1lLCBkaXJlY3Rpb24pIHtcbiAgaWYgKCFjb2x1bW5OYW1lKSByZXR1cm4gMFxuXG4gIC8vIEdldCB0aGUgdmFsdWVzIHRvIGNvbXBhcmUgYmFzZWQgb24gdGhlIGNvbHVtbk5hbWVcbiAgYSA9IGNvbnZlcnRUb1ByaW1pdGl2ZSgkKGEpLmF0dHIoJ2RhdGEtc29ydGFibGUtJyArIGNvbHVtbk5hbWUpKVxuICBiID0gY29udmVydFRvUHJpbWl0aXZlKCQoYikuYXR0cignZGF0YS1zb3J0YWJsZS0nICsgY29sdW1uTmFtZSkpXG4gIG91dHB1dCA9IDBcblxuICAvLyBHZXQgdGhlIGRpcmVjdGlvbiB0byBzb3J0IChkZWZhdWx0IGlzIGBhc2NgKVxuICBkaXJlY3Rpb24gPSBkaXJlY3Rpb24gfHwgJ2FzYydcbiAgc3dpdGNoIChkaXJlY3Rpb24pIHtcbiAgICBjYXNlICdhc2MnOlxuICAgIGNhc2UgJ2FzY2VuZGluZyc6XG4gICAgY2FzZSAxOlxuICAgICAgaWYgKGEgPiBiKSB7XG4gICAgICAgIG91dHB1dCA9IDFcbiAgICAgIH0gZWxzZSBpZiAoYSA8IGIpIHtcbiAgICAgICAgb3V0cHV0ID0gLTFcbiAgICAgIH1cbiAgICAgIGJyZWFrXG5cbiAgICBjYXNlICdkZXNjJzpcbiAgICBjYXNlICdkZXNjZW5kaW5nJzpcbiAgICBjYXNlIC0xOlxuICAgICAgaWYgKGEgPCBiKSB7XG4gICAgICAgIG91dHB1dCA9IDFcbiAgICAgIH0gZWxzZSBpZiAoYSA+IGIpIHtcbiAgICAgICAgb3V0cHV0ID0gLTFcbiAgICAgIH1cbiAgICAgIGJyZWFrXG4gIH1cblxuICAvLyBAZGVidWcgY29uc29sZS5sb2coJ3NvcnRDb21wYXJlJywgYSwgYiwgY29sdW1uTmFtZSwgZGlyZWN0aW9uLCBvdXRwdXQpXG4gIHJldHVybiBvdXRwdXRcbn1cblxuLypcbiAqIFJlc2V0IHRoZSBjb250ZW50cycgb3JkZXIgYmFjayB0byB0aGUgb3JpZ2luYWxcbiAqXG4gKiBAbWV0aG9kIHJlc2V0XG4gKiBAcmV0dXJucyB7U29ydGFibGV9XG4gKi9cblNvcnRhYmxlLnByb3RvdHlwZS5yZXNldCA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgaWYgKHNlbGYuc2V0dGluZ3Muc2F2ZW9yaWdpbmFsb3JkZXIpIHtcbiAgICBzZWxmLiRlbGVtLnRyaWdnZXIoJ3NvcnRhYmxlOnJlc2V0JylcbiAgICByZXR1cm4gc2VsZi5zb3J0KCdvcmlnaW5hbC1vcmRlcicsICdhc2MnKVxuICB9XG5cbiAgcmV0dXJuIHNlbGZcbn1cblxuLypcbiAqIERlc3Ryb3kgdGhlIFNvcnRhYmxlIGluc3RhbmNlXG4gKlxuICogQG1ldGhvZCBkZXN0cm95XG4gKiBAcmV0dXJucyB7Vm9pZH1cbiAqL1xuU29ydGFibGUucHJvdG90eXBlLmRlc3Ryb3kgPSBmdW5jdGlvbiAoKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuXG4gIHNlbGYuJGVsZW1bMF0uU29ydGFibGUgPSBmYWxzZVxuICBkZWxldGUgc2VsZlxufVxuXG4vKlxuICogalF1ZXJ5IEFQSVxuICovXG4kLmZuLnVpU29ydGFibGUgPSBmdW5jdGlvbiAob3ApIHtcbiAgLy8gRmlyZSBhIGNvbW1hbmQgdG8gdGhlIFNvcnRhYmxlIG9iamVjdCwgZS5nLiAkKCdbZGF0YS1zb3J0YWJsZV0nKS51aVNvcnRhYmxlKCdzb3J0JywgJ2lkJywgJ2FzYycpXG4gIGlmICh0eXBlb2Ygb3AgPT09ICdzdHJpbmcnICYmIC9ec29ydHxyZXNldCQvLnRlc3Qob3ApKSB7XG4gICAgLy8gR2V0IGZ1cnRoZXIgYWRkaXRpb25hbCBhcmd1bWVudHMgdG8gYXBwbHkgdG8gdGhlIG1hdGNoZWQgY29tbWFuZCBtZXRob2RcbiAgICB2YXIgYXJncyA9IEFycmF5LnByb3RvdHlwZS5zbGljZS5jYWxsKGFyZ3VtZW50cylcbiAgICBhcmdzLnNoaWZ0KClcblxuICAgIC8vIEZpcmUgY29tbWFuZCBvbiBlYWNoIHJldHVybmVkIGVsZW0gaW5zdGFuY2VcbiAgICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgICBpZiAoZWxlbS5Tb3J0YWJsZSAmJiB0eXBlb2YgZWxlbS5Tb3J0YWJsZVtvcF0gPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgZWxlbS5Tb3J0YWJsZVtvcF0uYXBwbHkoZWxlbS5Tb3J0YWJsZSwgYXJncylcbiAgICAgIH1cbiAgICB9KVxuXG4gIC8vIFNldCB1cCBhIG5ldyBTb3J0YWJsZSBpbnN0YW5jZSBwZXIgZWxlbSAoaWYgb25lIGRvZXNuJ3QgYWxyZWFkeSBleGlzdClcbiAgfSBlbHNlIHtcbiAgICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgICBpZiAoIWVsZW0uU29ydGFibGUpIHtcbiAgICAgICAgbmV3IFNvcnRhYmxlKGVsZW0sIG9wKVxuICAgICAgfVxuICAgIH0pXG4gIH1cbn1cblxuLy8gQXV0by1hc3NpZ24gZnVuY3Rpb25hbGl0eSB0byBjb21wb25lbnRzIHdpdGggW2RhdGEtc29ydGFibGVdIGF0dHJpYnV0ZVxuJChkb2N1bWVudCkub24oJ3JlYWR5JywgZnVuY3Rpb24gKCkge1xuICAkKCdbZGF0YS1zb3J0YWJsZV0nKS51aVNvcnRhYmxlKClcbn0pXG5cbm1vZHVsZS5leHBvcnRzID0gU29ydGFibGVcbiIsIi8qXG4gKiBVbmlsZW5kIFRleHQgQ291bnRlclxuICovXG5cbnZhciAkID0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ2pRdWVyeSddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnalF1ZXJ5J10gOiBudWxsKVxudmFyIEVsZW1lbnRBdHRyc09iamVjdCA9IHJlcXVpcmUoJ0VsZW1lbnRBdHRyc09iamVjdCcpXG52YXIgVHdlZW4gPSByZXF1aXJlKCdUd2VlbicpXG52YXIgX18gPSByZXF1aXJlKCdfXycpXG5cbnZhciBUZXh0Q291bnQgPSBmdW5jdGlvbiAoZWxlbSwgb3B0aW9ucykge1xuICB2YXIgc2VsZiA9IHRoaXNcblxuICAvKlxuICAgKiBQcm9wZXJ0aWVzXG4gICAqL1xuICBzZWxmLiRlbGVtID0gJChlbGVtKVxuICBzZWxmLmVsZW0gPSBzZWxmLiRlbGVtWzBdXG4gIHNlbGYudGltZXIgPSBmYWxzZVxuICBzZWxmLnRyYWNrID0ge31cblxuICAvKlxuICAgKiBPcHRpb25zXG4gICAqL1xuICBzZWxmLnNldHRpbmdzID0gJC5leHRlbmQoe1xuICAgIGZwczogNjAsXG4gICAgc3RhcnRDb3VudDogcGFyc2VGbG9hdChzZWxmLiRlbGVtLnRleHQoKSksIC8vIGludC9mbG9hdFxuICAgIGVuZENvdW50OiAwLCAvLyBpbnQvZmxvYXRcbiAgICB0b3RhbFRpbWU6IDAsIC8vIGluIG1zXG4gICAgcm91bmRGbG9hdDogZmFsc2UsIC8vIGhvdyB0byByb3VuZCB0aGUgZmxvYXQgKGFuZCBpZilcbiAgICBmb3JtYXRPdXRwdXQ6IGZhbHNlXG4gIH0sIEVsZW1lbnRBdHRyc09iamVjdChlbGVtLCB7XG4gICAgZnBzOiAnZGF0YS1mcHMnLFxuICAgIHN0YXJ0Q291bnQ6ICdkYXRhLXN0YXJ0LWNvdW50JyxcbiAgICBlbmRDb3VudDogJ2RhdGEtZW5kLWNvdW50JyxcbiAgICB0b3RhbFRpbWU6ICdkYXRhLXRvdGFsLXRpbWUnLFxuICAgIHJvdW5kRmxvYXQ6ICdkYXRhLXJvdW5kLWZsb2F0J1xuICB9KSwgb3B0aW9ucylcblxuICAvKlxuICAgKiBVSVxuICAgKi9cbiAgc2VsZi4kZWxlbS5hZGRDbGFzcygndWktdGV4dC1jb3VudCcpXG5cbiAgLypcbiAgICogSW5pdGlhbGlzaW5nXG4gICAqL1xuICAvLyBFbnN1cmUgZWxlbWVudCBoYXMgZGlyZWN0IGFjY2VzcyB0byBpdHMgVGV4dENvdW50XG4gIHNlbGYuZWxlbS5UZXh0Q291bnQgPSBzZWxmXG5cbiAgLy8gU2V0IHRoZSBpbml0aWFsIHRyYWNraW5nIHZhbHVlc1xuICBzZWxmLnJlc2V0Q291bnQoKVxuXG4gIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZyggc2VsZiApXG5cbiAgcmV0dXJuIHNlbGZcbn1cblxuLypcbiAqIE1ldGhvZHNcbiAqL1xuLy8gUmVzZXQgY291bnRcblRleHRDb3VudC5wcm90b3R5cGUucmVzZXRDb3VudCA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG4gIHNlbGYuc3RvcENvdW50KClcblxuICAvLyBSZXNldCB0aGUgdHJhY2tpbmcgdmFyc1xuICBzZWxmLnRyYWNrID0ge1xuICAgIGZwczogICAgICAgIHBhcnNlSW50KHNlbGYuc2V0dGluZ3MuZnBzLCAxMCkgfHwgNjAsICAgICAgLy8gaW50XG4gICAgc3RhcnQ6ICAgICAgcGFyc2VGbG9hdChTdHJpbmcoc2VsZi5zZXR0aW5ncy5zdGFydENvdW50KS5yZXBsYWNlKC9bXlxcZFxcLVxcLl0rL2csICcnKSkgfHwgMCwgIC8vIGNhbiBiZSBpbnQvZmxvYXRcbiAgICBjdXJyZW50OiAgICAwLFxuICAgIGVuZDogICAgICAgIHBhcnNlRmxvYXQoc2VsZi5zZXR0aW5ncy5lbmRDb3VudCkgfHwgMCwgICAgLy8gY2FuIGJlIGludC9mbG9hdFxuICAgIHRvdGFsOiAgICAgIHBhcnNlSW50KHNlbGYuc2V0dGluZ3MudG90YWxUaW1lLCAxMCkgfHwgMCwgLy8gaW50XG4gICAgcHJvZ3Jlc3M6ICAgMCAvLyBmbG9hdDogZnJvbSAwIHRvIDFcbiAgfVxuXG4gIHNlbGYudHJhY2sudGltZUluY3JlbWVudCA9IE1hdGguY2VpbChzZWxmLnRyYWNrLnRvdGFsIC8gc2VsZi50cmFjay5mcHMpIHx8IDBcbiAgc2VsZi50cmFjay5pbmNyZW1lbnQgPSAoKHNlbGYudHJhY2suZW5kIC0gc2VsZi50cmFjay5zdGFydCkgLyBzZWxmLnRyYWNrLnRpbWVJbmNyZW1lbnQpIHx8IDBcbiAgc2VsZi50cmFjay5jdXJyZW50ID0gc2VsZi50cmFjay5zdGFydFxuXG4gIC8vIFJlc2V0IHRoZSBjb3VudFxuICBzZWxmLnNldFRleHQoc2VsZi50cmFjay5jdXJyZW50KVxufVxuXG4vLyBTdGFydCBjb3VudGluZ1xuVGV4dENvdW50LnByb3RvdHlwZS5zdGFydENvdW50ID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgaWYgKCBzZWxmLmNvdW50RGlyZWN0aW9uKCkgIT09IDAgJiYgc2VsZi50cmFjay5zdGFydCAhPSBzZWxmLnRyYWNrLmVuZCApIHtcbiAgICBzZWxmLnRpbWVyID0gc2V0SW50ZXJ2YWwoIGZ1bmN0aW9uICgpIHtcbiAgICAgIHNlbGYuaW5jcmVtZW50Q291bnQoKVxuICAgIH0sIHNlbGYudHJhY2sudGltZUluY3JlbWVudCApXG4gIH1cbn1cblxuLy8gSW5jcmVtZW50IHRoZSBjb3VudFxuVGV4dENvdW50LnByb3RvdHlwZS5pbmNyZW1lbnRDb3VudCA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG4gIC8vIEluY3JlbWVudCB0aGUgY291bnRcbiAgdmFyIGNvdW50ID0gc2VsZi50cmFjay5jdXJyZW50ID0gc2VsZi50cmFjay5jdXJyZW50ICsgc2VsZi50cmFjay5pbmNyZW1lbnRcblxuICAvLyBQcm9ncmVzc1xuICBzZWxmLnRyYWNrLnByb2dyZXNzID0gKHNlbGYudHJhY2suY3VycmVudCAvIE1hdGgubWF4KHNlbGYudHJhY2suc3RhcnQsIHNlbGYudHJhY2suZW5kKSlcblxuICAvLyBSb3VuZCBmbG9hdFxuICBpZiAoc2VsZi5zZXR0aW5ncy5yb3VuZEZsb2F0KSB7XG4gICAgc3dpdGNoIChzZWxmLnNldHRpbmdzLnJvdW5kRmxvYXQpIHtcbiAgICAgIGNhc2UgJ3JvdW5kJzpcbiAgICAgICAgY291bnQgPSBNYXRoLnJvdW5kKGNvdW50KVxuICAgICAgICBicmVha1xuXG4gICAgICBjYXNlICdjZWlsJzpcbiAgICAgICAgY291bnQgPSBNYXRoLmNlaWwoY291bnQpXG4gICAgICAgIGJyZWFrXG5cbiAgICAgIGNhc2UgJ2Zsb29yJzpcbiAgICAgICAgY291bnQgPSBNYXRoLmZsb29yKGNvdW50KVxuICAgICAgICBicmVha1xuICAgIH1cbiAgfVxuXG4gIC8vIFNldCB0aGUgY291bnQgdGV4dFxuICBzZWxmLnNldFRleHQoY291bnQpXG5cbiAgLy8gRW5kIHRoZSBjb3VudCBhdCBlbmQgb2YgcHJvZ3Jlc3NcbiAgaWYgKCAoc2VsZi5jb3VudERpcmVjdGlvbigpID09PSAgMSAmJiBzZWxmLnRyYWNrLmN1cnJlbnQgPCBzZWxmLnRyYWNrLmVuZCkgfHxcbiAgICAgICAoc2VsZi5jb3VudERpcmVjdGlvbigpID09PSAtMSAmJiBzZWxmLnRyYWNrLmN1cnJlbnQgPiBzZWxmLnRyYWNrLmVuZCkgICAgKSB7XG4gICAgc2VsZi5lbmRDb3VudCgpXG4gIH1cbn1cblxuLy8gU2V0IHRoZSB0ZXh0XG5UZXh0Q291bnQucHJvdG90eXBlLnNldFRleHQgPSBmdW5jdGlvbiAoIGNvdW50ICkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgLy8gRm9ybWF0IHRoZSBjb3VudFxuICBpZiAoIHR5cGVvZiBzZWxmLnNldHRpbmdzLmZvcm1hdE91dHB1dCA9PT0gJ2Z1bmN0aW9uJyApIHtcbiAgICAgY291bnQgPSBzZWxmLnNldHRpbmdzLmZvcm1hdE91dHB1dC5hcHBseShzZWxmLCBbY291bnRdKVxuICB9XG5cbiAgLy8gU2V0IHRoZSBlbGVtZW50J3MgdGV4dFxuICBzZWxmLiRlbGVtLnRleHQoY291bnQpXG59XG5cbi8vIFN0b3AgY291bnRpbmdcblRleHRDb3VudC5wcm90b3R5cGUuc3RvcENvdW50ID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgY2xlYXJUaW1lb3V0KHNlbGYudGltZXIpXG4gIHNlbGYudGltZXIgPSBmYWxzZVxufVxuXG4vLyBTZWVrIHRvIGVuZFxuVGV4dENvdW50LnByb3RvdHlwZS5lbmRDb3VudCA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG4gIHNlbGYuc3RvcENvdW50KClcbiAgc2VsZi50cmFjay5wcm9ncmVzcyA9IDFcbiAgc2VsZi50cmFjay5jdXJyZW50ID0gc2VsZi50cmFjay5lbmRcbiAgc2VsZi5zZXRUZXh0KHNlbGYudHJhY2suZW5kKVxufVxuXG4vLyBDaGVjayBpZiBoYXMgc3RhcnRlZFxuVGV4dENvdW50LnByb3RvdHlwZS5zdGFydGVkID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgcmV0dXJuIHNlbGYudGltZXIgIT09IGZhbHNlXG59XG5cbi8vIENoZWNrIGlmIGhhcyBzdG9wcGVkXG5UZXh0Q291bnQucHJvdG90eXBlLnN0b3BwZWQgPSBmdW5jdGlvbiAoKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICByZXR1cm4gIXNlbGYudGltZXJcbn1cblxuLy8gR2V0IGRpcmVjdGlvbiBvZiBjb3VudFxuLy8gMTogdXB3YXJkXG4vLyAtMTogZG93bndhcmRcbi8vIDA6IG5vd2hlcmVcblRleHRDb3VudC5wcm90b3R5cGUuY291bnREaXJlY3Rpb24gPSBmdW5jdGlvbiAoKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICBpZiAoIHNlbGYudHJhY2suc3RhcnQgPiBzZWxmLnRyYWNrLmVuZCApIHJldHVybiAgMVxuICBpZiAoIHNlbGYudHJhY2suc3RhcnQgPCBzZWxmLnRyYWNrLmVuZCApIHJldHVybiAtMVxuICByZXR1cm4gMFxufVxuXG4vKlxuICogalF1ZXJ5IFBsdWdpblxuICovXG4kLmZuLnVpVGV4dENvdW50ID0gZnVuY3Rpb24gKG9wKSB7XG4gIG9wID0gb3AgfHwge31cblxuICByZXR1cm4gdGhpcy5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgLy8gQGRlYnVnXG4gICAgLy8gY29uc29sZS5sb2coJ2Fzc2lnbiBUZXh0Q291bnQnLCBlbGVtKVxuXG4gICAgLy8gQWxyZWFkeSBhc3NpZ25lZCwgaWdub3JlIGVsZW1cbiAgICBpZiAoZWxlbS5oYXNPd25Qcm9wZXJ0eSgnVGV4dENvdW50JykpIHJldHVyblxuXG4gICAgdmFyICRlbGVtID0gJChlbGVtKVxuICAgIHZhciBpc1ByaWNlID0gL1tcXCRcXOKCrFxcwqNdLy50ZXN0KCRlbGVtLnRleHQoKSlcbiAgICB2YXIgbGltaXREZWNpbWFsID0gJGVsZW0uYXR0cignZGF0YS1yb3VuZC1mbG9hdCcpID8gMCA6IDIgLy8gU2V0IHNpdGUtd2lkZSBkZWZhdWx0cyBoZXJlXG4gICAgdmFyIHR3ZWVuQ291bnQgPSAkZWxlbS5hdHRyKCdkYXRhLXR3ZWVuLWNvdW50JykgfHwgZmFsc2UgLy8gU2V0IHNpdGUtd2lkZSBkZWZhdWx0cyBoZXJlXG4gICAgdmFyIGRlYnVnID0gJGVsZW0uYXR0cignZGF0YS1kZWJ1ZycpID09PSAndHJ1ZScgLy8gT3V0cHV0IGRlYnVnIHZhbHVlcyBmb3IgdGhpcyBpdGVtXG4gICAgaWYgKHR3ZWVuQ291bnQgJiYgIVR3ZWVuLmhhc093blByb3BlcnR5KHR3ZWVuQ291bnQpKSB0d2VlbkNvdW50ID0gZmFsc2VcblxuICAgIC8vIFVzZSBzZXBhcmF0ZSBmdW5jdGlvbnMgaGVyZSB0byByZWR1Y2UgbG9hZCB3aXRoaW4gZm9ybWF0T3V0cHV0IGNhbGxiYWNrXG4gICAgaWYgKHR3ZWVuQ291bnQpIHtcbiAgICAgIG9wLmZvcm1hdE91dHB1dCA9IGZ1bmN0aW9uIChjb3VudCkge1xuICAgICAgICAvLyBUd2VlbiB0aGUgbnVtYmVyXG4gICAgICAgIHZhciBuZXdDb3VudCA9IFR3ZWVuW3R3ZWVuQ291bnRdLmFwcGx5KHRoaXMsIFt0aGlzLnRyYWNrLnByb2dyZXNzLCB0aGlzLnRyYWNrLnN0YXJ0LCBNYXRoLm1heCh0aGlzLnRyYWNrLnN0YXJ0LCB0aGlzLnRyYWNrLmVuZCkgLSBNYXRoLm1pbih0aGlzLnRyYWNrLnN0YXJ0LCB0aGlzLnRyYWNrLmVuZCksIDFdKVxuXG4gICAgICAgIC8vIEBkZWJ1ZyBpZiAoZGVidWcpIGNvbnNvbGUubG9nKHRoaXMudHJhY2sucHJvZ3Jlc3MsIGNvdW50ICsgJyA9PiAnICsgbmV3Q291bnQpXG5cbiAgICAgICAgLy8gRm9ybWF0IHRoZSBvdXRwdXQgbnVtYmVyXG4gICAgICAgIHJldHVybiBfXy5mb3JtYXROdW1iZXIobmV3Q291bnQsIGxpbWl0RGVjaW1hbCwgaXNQcmljZSlcbiAgICAgIH1cbiAgICB9IGVsc2Uge1xuICAgICAgb3AuZm9ybWF0T3V0cHV0ID0gZnVuY3Rpb24gKGNvdW50KSB7XG4gICAgICAgIC8vIEZvcm1hdCB0aGUgb3V0cHV0IG51bWJlclxuICAgICAgICByZXR1cm4gX18uZm9ybWF0TnVtYmVyKGNvdW50LCBsaW1pdERlY2ltYWwsIGlzUHJpY2UpXG4gICAgICB9XG4gICAgfVxuXG4gICAgLy8gSW5pdGlhbGlzZSB0aGUgdGV4dCBjb3VudFxuICAgIG5ldyBUZXh0Q291bnQoZWxlbSwgb3ApXG5cbiAgICAvLyBAZGVidWdcbiAgICAvLyBjb25zb2xlLmxvZygnaW5pdGlhbGlzZWQgVGV4dENvdW50JywgZWxlbS5UZXh0Q291bnQpXG4gIH0pXG59XG5cbi8qXG4gKiBqUXVlcnkgRXZlbnRzXG4gKi9cbiQoZG9jdW1lbnQpXG4gIC8vIEluaXRhbGlzZSBhbnkgZWxlbWVudCB3aXRoIHRoZSBgLnVpLXRleHQtY291bnRgIGNsYXNzXG4gIC5vbigncmVhZHknLCBmdW5jdGlvbiAoKSB7XG4gICAgLy8gQXBwbGllcyB0byBhbGwgZ2VuZXJpYyAudWktdGV4dC1jb3VudCBlbGVtZW50c1xuICAgICQoJy51aS10ZXh0LWNvdW50JykudWlUZXh0Q291bnQoKVxuICB9KVxuXG5tb2R1bGUuZXhwb3J0cyA9IFRleHRDb3VudFxuIiwiLypcbiAqIFRpbWVDb3VudFxuICovXG5cbnZhciAkID0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ2pRdWVyeSddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnalF1ZXJ5J10gOiBudWxsKVxudmFyIFV0aWxpdHkgPSByZXF1aXJlKCdVdGlsaXR5JylcbnZhciBFbGVtZW50QXR0cnNPYmplY3QgPSByZXF1aXJlKCdFbGVtZW50QXR0cnNPYmplY3QnKVxudmFyIF9fID0gcmVxdWlyZSgnX18nKVxuXG4vLyBPdmVyYWxsIHRpbWVyIGZvciB1cGRhdGluZyB0aW1lIGNvdW50cyAoYmV0dGVyIGluIHNpbmdsZSB0aW1lciB0aGFuIHBlciBlbGVtZW50IHNvIGFsbCByZWxhdGVkIHRpbWUgY291bnRlcnMgYXJlIHVwZGF0ZWQgYXQgdGhlIHNhbWUgdGltZSlcbnZhciBUaW1lQ291bnRUaW1lciA9IHNldEludGVydmFsKGZ1bmN0aW9uICgpIHtcbiAgdmFyICR0aW1lQ291bnRlcnMgPSAkKCcudWktdGltZS1jb3VudGluZycpXG4gIGlmICgkdGltZUNvdW50ZXJzLmxlbmd0aCA+IDApIHtcbiAgICAkdGltZUNvdW50ZXJzLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICAgIGlmIChlbGVtLmhhc093blByb3BlcnR5KCdUaW1lQ291bnQnKSkge1xuICAgICAgICBlbGVtLlRpbWVDb3VudC51cGRhdGUoKVxuICAgICAgfVxuICAgIH0pXG4gIH1cbn0sIDEwMDApXG5cbi8vIFRpbWVDb3VudCBDbGFzc1xudmFyIFRpbWVDb3VudCA9IGZ1bmN0aW9uIChlbGVtLCBvcHRpb25zKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuXG4gIC8vIFRoZSByZWxhdGVkIGVsZW1lbnRcbiAgc2VsZi4kZWxlbSA9ICQoZWxlbSlcbiAgaWYgKHNlbGYuJGVsZW0ubGVuZ3RoID09PSAwKSByZXR1cm5cblxuICAvLyBTZXR0aW5nc1xuICBzZWxmLnNldHRpbmdzID0gJC5leHRlbmQoe1xuICAgIHN0YXJ0RGF0ZTogZmFsc2UsXG4gICAgZW5kRGF0ZTogZmFsc2VcbiAgfSwgRWxlbWVudEF0dHJzT2JqZWN0KGVsZW0sIHtcbiAgICBzdGFydERhdGU6ICdkYXRhLXRpbWUtY291bnQtZnJvbScsXG4gICAgZW5kRGF0ZTogJ2RhdGEtdGltZS1jb3VudC10bydcbiAgfSksIG9wdGlvbnMpXG5cbiAgLy8gU2V0IHVwIHRoZSBkYXRlc1xuICBpZiAoc2VsZi5zZXR0aW5ncy5zdGFydERhdGUgJiYgIShzZWxmLnNldHRpbmdzLnN0YXJ0RGF0ZSBpbnN0YW5jZW9mIERhdGUpKSBzZWxmLnNldHRpbmdzLnN0YXJ0RGF0ZSA9IG5ldyBEYXRlKHNlbGYuc2V0dGluZ3Muc3RhcnREYXRlKVxuICBpZiAoc2VsZi5zZXR0aW5ncy5lbmREYXRlICYmICEoc2VsZi5zZXR0aW5ncy5lbmREYXRlIGluc3RhbmNlb2YgRGF0ZSkpIHNlbGYuc2V0dGluZ3MuZW5kRGF0ZSA9IG5ldyBEYXRlKHNlbGYuc2V0dGluZ3MuZW5kRGF0ZSlcblxuICAvLyBUcmFja1xuICBzZWxmLnRyYWNrID0ge1xuICAgIGRpcmVjdGlvbjogKHNlbGYuc2V0dGluZ3Muc3RhcnREYXRlID4gc2VsZi5zZXR0aW5ncy5lbmREYXRlID8gLTEgOiAxKSxcbiAgICB0aW1lUmVtYWluaW5nOiBVdGlsaXR5LmdldFRpbWVSZW1haW5pbmcoc2VsZi5zZXR0aW5ncy5lbmREYXRlLCBzZWxmLnNldHRpbmdzLnN0YXJ0RGF0ZSlcbiAgfVxuXG4gIC8vIFVJXG4gIHNlbGYuJGVsZW0uYWRkQ2xhc3MoJ3VpLXRpbWUtY291bnRpbmcnKVxuXG4gIC8vIEF0dGFjaCByZWZlcmVuY2UgdG8gVGltZUNvdW50IHRvIGVsZW1cbiAgc2VsZi4kZWxlbVswXS5UaW1lQ291bnQgPSBzZWxmXG5cbiAgLy8gVHJpZ2dlciB0aGUgc3RhcnRpbmcgZXZlbnRcbiAgc2VsZi4kZWxlbS50cmlnZ2VyKCdUaW1lQ291bnQ6c3RhcnRpbmcnLCBbc2VsZiwgc2VsZi50cmFjay50aW1lUmVtYWluaW5nXSlcblxuICAvLyBVcGRhdGUgdGhlIHRpbWUgcmVtYWluaW5nXG4gIHNlbGYudXBkYXRlKClcblxuICByZXR1cm4gc2VsZlxufVxuXG4vLyBVcGRhdGUgdGhlIHRpbWUgY291bnRcblRpbWVDb3VudC5wcm90b3R5cGUudXBkYXRlID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgc2VsZi50cmFjay50aW1lUmVtYWluaW5nID0gVXRpbGl0eS5nZXRUaW1lUmVtYWluaW5nKHNlbGYuc2V0dGluZ3MuZW5kRGF0ZSwgc2VsZi5zZXR0aW5ncy5zdGFydERhdGUpXG5cbiAgLy8gVHJpZ2dlciB0aGUgdXBkYXRlIGV2ZW50IG9uIHRoZSBVSSBlbGVtZW50XG4gIHNlbGYuJGVsZW0udHJpZ2dlcignVGltZUNvdW50OnVwZGF0ZScsIFtzZWxmLCBzZWxmLnRyYWNrLnRpbWVSZW1haW5pbmddKVxuXG4gIC8vIENvdW50IGNvbXBsZXRlXG4gIGlmICgoc2VsZi50cmFjay5kaXJlY3Rpb24gPiAwICYmIHNlbGYudHJhY2sudGltZVJlbWFpbmluZy50b3RhbCA8PSAwKSB8fFxuICAgICAgKHNlbGYudHJhY2suZGlyZWN0aW9uIDwgMCAmJiBzZWxmLnRyYWNrLnRpbWVSZW1haW5pbmcudG90YWwgPj0gMCkpIHtcbiAgICBzZWxmLmNvbXBsZXRlKClcbiAgfVxufVxuXG4vLyBDb21wbGV0ZSB0aGUgdGltZSBjb3VudFxuVGltZUNvdW50LnByb3RvdHlwZS5jb21wbGV0ZSA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgLy8gVHJpZ2dlciB0aGUgY29tcGxldGluZyBldmVudCBvbiB0aGUgVUkgZWxlbWVudFxuICBzZWxmLiRlbGVtLnRyaWdnZXIoJ1RpbWVDb3VudDpjb21wbGV0aW5nJywgW3NlbGYsIHNlbGYudHJhY2sudGltZVJlbWFpbmluZ10pXG5cbiAgLy8gUmVtb3ZlIHRoZSAudWktdGltZS1jb3VudGluZyBjbGFzc1xuICBzZWxmLiRlbGVtLnJlbW92ZUNsYXNzKCcudWktdGltZS1jb3VudGluZycpXG5cbiAgLy8gVHJpZ2dlciB0aGUgY29tcGxldGVkIGV2ZW50IG9uIHRoZSBVSSBlbGVtZW50XG4gIHNlbGYuJGVsZW0udHJpZ2dlcignVGltZUNvdW50OmNvbXBsZXRlZCcsIFtzZWxmLCBzZWxmLnRyYWNrLnRpbWVSZW1haW5pbmddKVxufVxuXG4vKlxuICogalF1ZXJ5IHBsdWdpblxuICovXG4kLmZuLnVpVGltZUNvdW50ID0gZnVuY3Rpb24gKG9wKSB7XG4gIHJldHVybiB0aGlzLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICBpZiAoIWVsZW0uaGFzT3duUHJvcGVydHkoJ1RpbWVDb3VudCcpKSB7XG4gICAgICBuZXcgVGltZUNvdW50KGVsZW0sIG9wKVxuICAgIH1cbiAgfSlcbn1cblxuLypcbiAqIGpRdWVyeSBJbml0aWFsaXNhdGlvblxuICovXG4kKGRvY3VtZW50KVxuICAub24oJ3JlYWR5JywgZnVuY3Rpb24gKCkge1xuICAgICQoJy51aS10aW1lLWNvdW50JykudWlUaW1lQ291bnQoKVxuICB9KVxuIiwiLypcbiAqIFVuaWxlbmQgRGljdGlvbmFyeVxuICogRW5hYmxlcyBsb29raW5nIHVwIHRleHQgdG8gY2hhbmdlIHBlciB1c2VyJ3MgbGFuZ1xuICogV29ya3Mgc2FtZSBhcyBtb3N0IGkxOG4gZnVuY3Rpb25zXG4gKiBUaGlzIGlzIGFsc28gdXNlZCBieSBUd2lnIHRvIGxvb2sgdXAgZGljdGlvbmFyeSBlbnRyaWVzXG4gKiBTZWUgZ3VscGZpbGUuanMgdG8gc2VlIGhvdyBpdCBpcyBsb2FkZWQgaW50byBUd2lnXG4gKi9cblxudmFyIERpY3Rpb25hcnkgPSBmdW5jdGlvbiAoZGljdGlvbmFyeSwgbGFuZykge1xuICB2YXIgc2VsZiA9IHRoaXNcblxuICBpZiAoIWRpY3Rpb25hcnkpIHJldHVybiBmYWxzZVxuXG4gIHNlbGYuZGVmYXVsdExhbmcgPSBsYW5nIHx8ICdmcidcbiAgc2VsZi5kaWN0aW9uYXJ5ID0gZGljdGlvbmFyeVxuXG4gIC8vIEdldCBhIG1lc3NhZ2Ugd2l0aGluIHRoZSBkaWN0aW9uYXJ5XG4gIHNlbGYuX18gPSBmdW5jdGlvbiAoZmFsbGJhY2tUZXh0LCB0ZXh0S2V5LCBsYW5nKSB7XG4gICAgbGFuZyA9IGxhbmcgfHwgc2VsZi5kZWZhdWx0TGFuZ1xuXG4gICAgLy8gRW5zdXJlIGRpY3Rpb25hcnkgc3VwcG9ydHMgbGFuZ1xuICAgIGlmICghc2VsZi5zdXBwb3J0c0xhbmcobGFuZykpIHtcbiAgICAgIC8vIFNlZSBpZiBnZW5lcmFsIGxhbmd1YWdlIGV4aXN0c1xuICAgICAgaWYgKGxhbmcubWF0Y2goJy0nKSkgbGFuZyA9IGxhbmcuc3BsaXQoJy0nKVswXVxuXG4gICAgICAvLyBHbyB0byBkZWZhdWx0XG4gICAgICBpZiAoIXNlbGYuc3VwcG9ydHNMYW5nKGxhbmcpKSBsYW5nID0gc2VsZi5kZWZhdWx0TGFuZ1xuXG4gICAgICAvLyBEZWZhdWx0IG5vdCBzdXBwb3J0ZWQ/IFVzZSB0aGUgZmlyc3QgbGFuZyBlbnRyeSBpbiB0aGUgZGljdGlvbmFyeVxuICAgICAgaWYgKCFzZWxmLnN1cHBvcnRzTGFuZyhsYW5nKSkge1xuICAgICAgICBmb3IgKHggaW4gc2VsZi5kaWN0aW9uYXJ5KSB7XG4gICAgICAgICAgbGFuZyA9IHhcbiAgICAgICAgICBicmVha1xuICAgICAgICB9XG4gICAgICB9XG4gICAgfVxuXG4gICAgLy8gRW5zdXJlIHRoZSB0ZXh0S2V5IGV4aXN0cyB3aXRoaW4gdGhlIHNlbGVjdGVkIGxhbmcgZGljdGlvbmFyeVxuICAgIGlmIChzZWxmLmRpY3Rpb25hcnlbbGFuZ10uaGFzT3duUHJvcGVydHkodGV4dEtleSkpIHJldHVybiBkaWN0aW9uYXJ5W2xhbmddW3RleHRLZXldXG5cbiAgICAvLyBGYWxsYmFjayB0ZXh0XG4gICAgcmV0dXJuIGZhbGxiYWNrVGV4dFxuXG4gICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdFcnJvcjogdGV4dEtleSBub3QgZm91bmQgPT4gZGljdGlvbmFyeS4nICsgbGFuZyArICcuJyArIHRleHRLZXkpXG4gICAgcmV0dXJuICd7IyBFcnJvcjogdGV4dEtleSBub3QgZm91bmQgPT4gZGljdGlvbmFyeS4nICsgbGFuZyArICcuJyArIHRleHRLZXkgKyAnICN9J1xuICB9XG5cbiAgLy8gU2V0IHRoZSBkZWZhdWx0IGxhbmdcbiAgc2VsZi5zZXREZWZhdWx0TGFuZyA9IGZ1bmN0aW9uIChsYW5nKSB7XG4gICAgc2VsZi5kZWZhdWx0TGFuZyA9IGxhbmdcbiAgfVxuXG4gIC8vIENoZWNrIGlmIHRoZSBEaWN0aW9uYXJ5IHN1cHBvcnRzIGEgbGFuZ3VhZ2VcbiAgc2VsZi5zdXBwb3J0c0xhbmcgPSBmdW5jdGlvbiAobGFuZykge1xuICAgIHJldHVybiBzZWxmLmRpY3Rpb25hcnkuaGFzT3duUHJvcGVydHkobGFuZylcbiAgfVxuXG4gIC8vIEFkZCBzZXBzIHRvIG51bWJlciAodGhvdXNhbmQgYW5kIGRlY2ltYWwpXG4gIC8vIEFkYXB0ZWQgZnJvbTogaHR0cDovL3d3dy5tcmVka2ouY29tL2phdmFzY3JpcHQvbmZiYXNpYy5odG1sXG4gIHNlbGYuYWRkTnVtYmVyU2VwcyA9IGZ1bmN0aW9uIChudW1iZXIsIG1pbGxpU2VwLCBkZWNpbWFsU2VwLCBsaW1pdERlY2ltYWwpIHtcbiAgICBudW1iZXIgKz0gJydcbiAgICB4ID0gbnVtYmVyLnNwbGl0KCcuJylcblxuICAgIC8vIEFkZCB0aGUgbWlsbGlTZXBcbiAgICBhID0geFswXVxuICAgIHZhciByZ3ggPSAvKFxcZCspKFxcZHszfSkvXG4gICAgd2hpbGUgKHJneC50ZXN0KGEpKSB7XG4gICAgICBhID0gYS5yZXBsYWNlKHJneCwgJyQxJyArIChtaWxsaVNlcCB8fCAnLCcpICsgJyQyJylcbiAgICB9XG5cbiAgICAvLyBMaW1pdCB0aGUgZGVjaW1hbFxuICAgIGlmIChsaW1pdERlY2ltYWwgPiAwKSB7XG4gICAgICBiID0gKHgubGVuZ3RoID4gMSA/IChkZWNpbWFsU2VwIHx8ICcuJykgKyB4WzFdLnN1YnN0cigwLCBsaW1pdERlY2ltYWwpIDogJycpXG4gICAgfSBlbHNlIHtcbiAgICAgIGIgPSAnJ1xuICAgIH1cblxuICAgIHJldHVybiBhICsgYlxuICB9XG5cbiAgLy8gRm9ybWF0IGEgbnVtYmVyIChhZGRzIHB1bmN0dWF0aW9uLCBjdXJyZW5jeSlcbiAgc2VsZi5mb3JtYXROdW1iZXIgPSBmdW5jdGlvbiAoaW5wdXQsIGxpbWl0RGVjaW1hbCwgaXNQcmljZSwgbGFuZykge1xuICAgIHZhciBudW1iZXIgPSBwYXJzZUZsb2F0KGlucHV0ICsgJycucmVwbGFjZSgvW15cXGRcXC1cXC5dKy8sICcnKSlcblxuICAgIC8vIERvbid0IG9wZXJhdGUgb24gbm9uLW51bWJlcnNcbiAgICBpZiAoaW5wdXQgPT09IEluZmluaXR5IHx8IGlzTmFOKG51bWJlcikpIHJldHVybiBpbnB1dFxuXG4gICAgLy8gTGFuZ3VhZ2Ugb3B0aW9uc1xuICAgIHZhciBudW1iZXJEZWNpbWFsID0gc2VsZi5fXygnLicsICdudW1iZXJEZWNpbWFsJywgbGFuZylcbiAgICB2YXIgbnVtYmVyTWlsbGkgPSBzZWxmLl9fKCcsJywgJ251bWJlck1pbGxpJywgbGFuZylcbiAgICB2YXIgbnVtYmVyQ3VycmVuY3kgPSBzZWxmLl9fKCckJywgJ251bWJlckN1cnJlbmN5JywgbGFuZylcblxuICAgIC8vIElzIHByaWNlXG4gICAgLy8gLS0gSWYgbm90IHNldCwgZGV0ZWN0IGlmIGhhcyBjdXJyZW5jeSBzeW1ib2wgaW4gaW5wdXRcbiAgICB2YXIgY3VycmVuY3kgPSBudW1iZXJDdXJyZW5jeVxuICAgIGlmICh0eXBlb2YgaXNQcmljZSA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgIGlzUHJpY2UgPSAvXltcXCRcXOKCrFxcwqNdLy50ZXN0KGlucHV0KVxuICAgICAgaWYgKGlzUHJpY2UpIHtcbiAgICAgICAgY3VycmVuY3kgPSBpbnB1dC5yZXBsYWNlKC8oXltcXCRcXOKCrFxcwqNdKS9nLCAnJDEnKVxuICAgICAgfVxuICAgIH1cblxuICAgIC8vIERlZmF1bHQgb3V0cHV0XG4gICAgdmFyIG91dHB1dCA9IGlucHV0XG5cbiAgICAvLyBMaW1pdCB0aGUgZGVjaW1hbHMgc2hvd25cbiAgICBpZiAodHlwZW9mIGxpbWl0RGVjaW1hbCA9PT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAgIGxpbWl0RGVjaW1hbCA9IGlzUHJpY2UgPyAyIDogMFxuICAgIH1cblxuICAgIC8vIE91dHB1dCB0aGUgZm9ybWF0dGVkIG51bWJlclxuICAgIG91dHB1dCA9IChpc1ByaWNlID8gY3VycmVuY3kgOiAnJykgKyBzZWxmLmFkZE51bWJlclNlcHMobnVtYmVyLCBudW1iZXJNaWxsaSwgbnVtYmVyRGVjaW1hbCwgbGltaXREZWNpbWFsKVxuXG4gICAgLy8gQGRlYnVnXG4gICAgLy8gY29uc29sZS5sb2coe1xuICAgIC8vICAgaW5wdXQ6IGlucHV0LFxuICAgIC8vICAgbnVtYmVyOiBudW1iZXIsXG4gICAgLy8gICBsaW1pdERlY2ltYWw6IGxpbWl0RGVjaW1hbCxcbiAgICAvLyAgIGlzUHJpY2U6IGlzUHJpY2UsXG4gICAgLy8gICBsYW5nOiBsYW5nLFxuICAgIC8vICAgbnVtYmVyRGVjaW1hbDogbnVtYmVyRGVjaW1hbCxcbiAgICAvLyAgIG51bWJlck1pbGxpOiBudW1iZXJNaWxsaSxcbiAgICAvLyAgIG51bWJlckN1cnJlbmN5OiBudW1iZXJDdXJyZW5jeSxcbiAgICAvLyAgIGN1cnJlbmN5OiBjdXJyZW5jeSxcbiAgICAvLyAgIG91dHB1dDogb3V0cHV0XG4gICAgLy8gfSlcblxuICAgIHJldHVybiBvdXRwdXRcbiAgfVxuXG4gIC8vIExvY2FsaXplIGEgbnVtYmVyXG4gIHNlbGYubG9jYWxpemVkTnVtYmVyID0gZnVuY3Rpb24gKGlucHV0LCBsaW1pdERlY2ltYWwsIGxhbmcpIHtcbiAgICByZXR1cm4gc2VsZi5mb3JtYXROdW1iZXIoaW5wdXQsIGxpbWl0RGVjaW1hbCB8fCAwLCBmYWxzZSwgbGFuZylcbiAgfVxuXG4gIC8vIExvY2FsaXplIGEgcHJpY2VcbiAgc2VsZi5sb2NhbGl6ZWRQcmljZSA9IGZ1bmN0aW9uIChpbnB1dCwgbGltaXREZWNpbWFsLCBsYW5nKSB7XG4gICAgcmV0dXJuIHNlbGYuZm9ybWF0TnVtYmVyKGlucHV0LCBsaW1pdERlY2ltYWwgfHwgMiwgdHJ1ZSwgbGFuZylcbiAgfVxufVxuXG5tb2R1bGUuZXhwb3J0cyA9IERpY3Rpb25hcnlcbiIsIi8qXG4gKiBFbGVtZW50IEF0dHJpYnV0ZXMgYXMgT2JqZWN0XG4gKlxuICogR2V0IGEgcmFuZ2Ugb2YgZWxlbWVudCBhdHRyaWJ1dGVzIGFzIGFuIG9iamVjdFxuICovXG5cbnZhciAkID0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ2pRdWVyeSddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnalF1ZXJ5J10gOiBudWxsKVxudmFyIFV0aWxpdHkgPSByZXF1aXJlKCdVdGlsaXR5JylcblxuLypcbiAqIEBtZXRob2QgRWxlbWVudEF0dHJzT2JqZWN0XG4gKiBAcGFyYW0ge01peGVkfSBlbGVtIENhbiBiZSB7U3RyaW5nfSBzZWxlY3Rvciwge0hUTUxFbGVtZW50fSBvciB7alF1ZXJ5T2JqZWN0fVxuICogQHBhcmFtIHtBcnJheX0gYXR0cnMgQW4gYXJyYXkgb2YgdGhlIHBvc3NpYmxlIGF0dHJpYnV0ZXMgdG8gcmV0cmlldmUgZnJvbSB0aGUgZWxlbWVudFxuICogQHJldHVybnMge09iamVjdH1cbiAqL1xudmFyIEVsZW1lbnRBdHRyc09iamVjdCA9IGZ1bmN0aW9uIChlbGVtLCBhdHRycykge1xuICB2YXIgJGVsZW0gPSAkKGVsZW0pXG4gIHZhciBvdXRwdXQgPSB7fVxuICB2YXIgYXR0clZhbHVlXG4gIHZhciBpXG5cbiAgLy8gTm8gZWxlbWVudC9hdHRyaWJ1dGVzXG4gIGlmICgkZWxlbS5sZW5ndGggPT09IDAgfHwgKHR5cGVvZiBhdHRycyAhPT0gJ29iamVjdCcgJiYgIShhdHRycyBpbnN0YW5jZW9mIEFycmF5KSkpIHJldHVybiB7fVxuXG4gIC8vIFByb2Nlc3MgYXR0cmlidXRlcyB2aWEgYXJyYXlcbiAgaWYgKGF0dHJzIGluc3RhbmNlb2YgQXJyYXkpIHtcbiAgICBmb3IgKGkgPSAwOyBpIDwgYXR0cnMubGVuZ3RoOyBpKyspIHtcbiAgICAgIGF0dHJWYWx1ZSA9IFV0aWxpdHkuY2hlY2tFbGVtQXR0ckZvclZhbHVlKGVsZW0sIGF0dHJzW2ldKVxuICAgICAgaWYgKHR5cGVvZiBhdHRyVmFsdWUgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgIG91dHB1dFthdHRyc1tpXV0gPSBVdGlsaXR5LmNvbnZlcnRUb1ByaW1pdGl2ZShhdHRyVmFsdWUpXG4gICAgICB9XG4gICAgfVxuXG4gIC8vIFByb2Nlc3MgYXR0cmlidXRlcyB2aWEgb2JqZWN0IGtleS12YWx1ZVxuICB9IGVsc2UgaWYgKHR5cGVvZiBhdHRycyA9PT0gJ29iamVjdCcpIHtcbiAgICBmb3IgKGkgaW4gYXR0cnMpIHtcbiAgICAgIGF0dHJWYWx1ZSA9IFV0aWxpdHkuY2hlY2tFbGVtQXR0ckZvclZhbHVlKGVsZW0sIGF0dHJzW2ldKVxuICAgICAgaWYgKHR5cGVvZiBhdHRyVmFsdWUgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgIG91dHB1dFtpXSA9IFV0aWxpdHkuY29udmVydFRvUHJpbWl0aXZlKGF0dHJWYWx1ZSlcbiAgICAgIH1cbiAgICB9XG4gIH1cblxuICAvLyBAZGVidWdcbiAgLy8gY29uc29sZS5sb2coJ0VsZW1lbnRBdHRyc09iamVjdCcsIGVsZW0sIGF0dHJzLCBvdXRwdXQpXG5cbiAgcmV0dXJuIG91dHB1dFxufVxuXG5tb2R1bGUuZXhwb3J0cyA9IEVsZW1lbnRBdHRyc09iamVjdFxuIiwiLypcbiAqIEJvdW5kc1xuICogR2V0IHRoZSBib3VuZHMgb2YgYW4gZWxlbWVudFxuICogWW91IGNhbiBhbHNvIHBlcmZvcm0gb3RoZXIgb3BlcmF0aW9ucyBvbiB0aGUgYm91bmRzIChjb21iaW5lLCBzY2FsZSwgZXRjLilcbiAqIFRoaXMgaXMgdXNlZCBwcmltYXJpbHkgYnkgV2F0Y2hTY3JvbGwgdG8gZGV0ZWN0IGlmIGFuIGVsZW1lbnRcbiAqIGlzIHdpdGhpbiB0aGUgdmlzaWJsZSBhcmVhIG9mIGFub3RoZXIgZWxlbWVudFxuICovXG5cbnZhciAkID0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ2pRdWVyeSddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnalF1ZXJ5J10gOiBudWxsKVxudmFyIFV0aWxpdHkgPSByZXF1aXJlKCdVdGlsaXR5JylcblxuZnVuY3Rpb24gaXNFbGVtZW50KG8pe1xuICAvLyBSZWd1bGFyIGVsZW1lbnQgY2hlY2tcbiAgcmV0dXJuIChcbiAgICB0eXBlb2YgSFRNTEVsZW1lbnQgPT09IFwib2JqZWN0XCIgPyBvIGluc3RhbmNlb2YgSFRNTEVsZW1lbnQgOiAvL0RPTTJcbiAgICBvICYmIHR5cGVvZiBvID09PSBcIm9iamVjdFwiICYmIG8gIT09IG51bGwgJiYgby5ub2RlVHlwZSA9PT0gMSAmJiB0eXBlb2Ygby5ub2RlTmFtZT09PVwic3RyaW5nXCJcbiAgKVxufVxuXG4vKlxuICogQm91bmRzIG9iamVjdCBjbGFzc1xuICovXG52YXIgQm91bmRzID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgdmFyIGFyZ3MgPSBBcnJheS5wcm90b3R5cGUuc2xpY2UuY2FsbChhcmd1bWVudHMpXG5cbiAgLy8gUHJvcGVydGllc1xuICBzZWxmLmlkID0gVXRpbGl0eS5yYW5kb21TdHJpbmcoKVxuICBzZWxmLmNvb3JkcyA9IFswLCAwLCAwLCAwXVxuICBzZWxmLndpZHRoID0gMFxuICBzZWxmLmhlaWdodCA9IDBcbiAgc2VsZi5lbGVtID0gdW5kZWZpbmVkXG4gIHNlbGYuJHZpeiA9IHVuZGVmaW5lZFxuXG4gIC8vIEluaXRpYWxpc2Ugd2l0aCBhbnkgYXJndW1lbnRzLCBlLmcuIG5ldyBCb3VuZHMoMCwgMCwgMTAwLCAxMDApXG4gIGlmIChhcmdzLmxlbmd0aCA+IDApIHNlbGYuc2V0Qm91bmRzLmFwcGx5KHNlbGYsIGFyZ3MpXG5cbiAgcmV0dXJuIHNlbGZcbn1cblxuLy8gU2V0IHRoZSBib3VuZHMgKGFuZCB1cGRhdGUgd2lkdGggYW5kIGhlaWdodCBwcm9wZXJ0aWVzIHRvbylcbi8vIEByZXR1cm5zIHtCb3VuZHN9XG5Cb3VuZHMucHJvdG90eXBlLnNldEJvdW5kcyA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgLy8gQ2hlY2sgaWYgNCBhcmd1bWVudHMgZ2l2ZW46IHgxLCB5MSwgeDIsIHkyXG4gIHZhciBhcmdzID0gQXJyYXkucHJvdG90eXBlLnNsaWNlLmNhbGwoYXJndW1lbnRzKVxuXG4gIC8vIFNpbmdsZSBhcmd1bWVudCBnaXZlblxuICBpZiAoYXJncy5sZW5ndGggPT09IDEpIHtcbiAgICAvLyBCb3VuZHMgb2JqZWN0IGdpdmVuXG4gICAgaWYgKGFyZ3NbMF0gaW5zdGFuY2VvZiBCb3VuZHMpIHtcbiAgICAgIHJldHVybiBhcmdzWzBdLmNsb25lKClcblxuICAgIC8vIDQgcG9pbnRzIGdpdmVuOiB4MSwgeTEsIHgyLCB5MlxuICAgIH0gZWxzZSBpZiAoYXJnc1swXSBpbnN0YW5jZW9mIEFycmF5ICYmIGFyZ3NbMF0ubGVuZ3RoID09PSA0KSB7XG4gICAgICBhcmdzID0gYXJnc1swXVxuXG4gICAgLy8gU3RyaW5nIG9yIEhUTUwgZWxlbWVudCBnaXZlblxuICAgIH0gZWxzZSBpZiAodHlwZW9mIGFyZ3NbMF0gPT09ICdzdHJpbmcnIHx8IGlzRWxlbWVudChhcmdzWzBdKSB8fCBhcmdzWzBdID09PSB3aW5kb3cpIHtcbiAgICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnc2V0Qm91bmRzRnJvbUVsZW0nLCBhcmdzWzBdKVxuICAgICAgcmV0dXJuIHNlbGYuc2V0Qm91bmRzRnJvbUVsZW0oYXJnc1swXSlcbiAgICB9XG4gIH1cblxuICAvLyA0IGNvb3JkcyBnaXZlblxuICBpZiAoYXJncy5sZW5ndGggPT09IDQpIHtcbiAgICBmb3IgKHZhciBpID0gMDsgaSA8IGFyZ3MubGVuZ3RoOyBpKyspIHtcbiAgICAgIHNlbGYuY29vcmRzW2ldID0gYXJnc1tpXVxuICAgIH1cbiAgfVxuXG4gIC8vIFJlY2FsY3VsYXRlIHdpZHRoIGFuZCBoZWlnaHRcbiAgc2VsZi53aWR0aCA9IHNlbGYuZ2V0V2lkdGgoKVxuICBzZWxmLmhlaWdodCA9IHNlbGYuZ2V0SGVpZ2h0KClcblxuICByZXR1cm4gc2VsZlxufVxuXG4vLyBVcGRhdGUgdGhlIGJvdW5kcyAob25seSBpZiBlbGVtZW50IGlzIGF0dGFjaGVkKVxuLy8gQHJldHVybnMge1ZvaWR9XG5Cb3VuZHMucHJvdG90eXBlLnVwZGF0ZSA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgLy8gT25seSBpZiByZWxhdGVkIHRvIGFuIGVsZW1lbnRcbiAgaWYgKHNlbGYuZWxlbSkge1xuICAgIHNlbGYuc2V0Qm91bmRzRnJvbUVsZW0oc2VsZi5lbGVtKVxuICB9XG5cbiAgc2VsZi53aWR0aCA9IHNlbGYuZ2V0V2lkdGgoKVxuICBzZWxmLmhlaWdodCA9IHNlbGYuZ2V0SGVpZ2h0KClcblxuICAvLyBVcGRhdGUgdGhlIHZpelxuICBpZiAoc2VsZi5nZXRWaXooKS5sZW5ndGggPiAwKSBzZWxmLnNob3dWaXooKVxuXG4gIHJldHVybiBzZWxmXG59XG5cbi8vIENhbGN1bGF0ZSB0aGUgd2lkdGggb2YgdGhlIGJvdW5kc1xuLy8gQHJldHVybnMge051bWJlcn1cbkJvdW5kcy5wcm90b3R5cGUuZ2V0V2lkdGggPSBmdW5jdGlvbiAoKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICByZXR1cm4gc2VsZi5jb29yZHNbMl0gLSBzZWxmLmNvb3Jkc1swXVxufVxuXG4vLyBDYWxjdWxhdGUgdGhlIGhlaWdodCBvZiB0aGUgYm91bmRzXG4vLyBAcmV0dXJucyB7TnVtYmVyfVxuQm91bmRzLnByb3RvdHlwZS5nZXRIZWlnaHQgPSBmdW5jdGlvbiAoKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICByZXR1cm4gc2VsZi5jb29yZHNbM10gLSBzZWxmLmNvb3Jkc1sxXVxufVxuXG4vLyBTY2FsZSB0aGUgYm91bmRzOiBlLmcuIHNjYWxlKDIpID0+IGRvdWJsZSwgc2NhbGUoMSwgMikgPT4gZG91YmxlcyBvbmx5IGhlaWdodFxuLy8gQHJldHVybnMge0JvdW5kc31cbkJvdW5kcy5wcm90b3R5cGUuc2NhbGUgPSBmdW5jdGlvbiAoKSB7XG4gIHZhciBzZWxmID0gdGhpc1xuICB2YXIgYXJncyA9IEFycmF5LnByb3RvdHlwZS5zbGljZS5hcHBseShhcmd1bWVudHMpXG4gIHZhciB3aWR0aFxuICB2YXIgaGVpZ2h0XG4gIHZhciB4U2NhbGUgPSAxXG4gIHZhciB5U2NhbGUgPSAxXG5cbiAgLy8gRGVwZW5kaW5nIG9uIHRoZSBudW1iZXIgb2YgYXJndW1lbnRzLCBzY2FsZSB0aGUgYm91bmRzXG4gIHN3aXRjaCAoYXJncy5sZW5ndGgpIHtcbiAgICBjYXNlIDA6XG4gICAgICByZXR1cm5cblxuICAgIGNhc2UgMTpcbiAgICAgIGlmICh0eXBlb2YgYXJnc1swXSA9PT0gJ251bWJlcicpIHtcbiAgICAgICAgeFNjYWxlID0gYXJnc1swXVxuICAgICAgICB5U2NhbGUgPSBhcmdzWzBdXG4gICAgICB9XG4gICAgICBicmVha1xuXG4gICAgY2FzZSAyOlxuICAgICAgaWYgKHR5cGVvZiBhcmdzWzBdID09PSAnbnVtYmVyJykgeFNjYWxlID0gYXJnc1swXVxuICAgICAgaWYgKHR5cGVvZiBhcmdzWzFdID09PSAnbnVtYmVyJykgeVNjYWxlID0gYXJnc1sxXVxuICAgICAgYnJlYWtcbiAgfVxuXG4gIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnQm91bmRzLnNjYWxlJywgeFNjYWxlLCB5U2NhbGUpXG5cbiAgLy8gU2NhbGVcbiAgaWYgKHhTY2FsZSAhPT0gMSB8fCB5U2NhbGUgIT09IDEpIHtcbiAgICB3aWR0aCA9IHNlbGYuZ2V0V2lkdGgoKVxuICAgIGhlaWdodCA9IHNlbGYuZ2V0SGVpZ2h0KClcbiAgICBzZWxmLnNldEJvdW5kcyhcbiAgICAgIHNlbGYuY29vcmRzWzBdLFxuICAgICAgc2VsZi5jb29yZHNbMV0sXG4gICAgICBzZWxmLmNvb3Jkc1swXSArICh3aWR0aCAqIHhTY2FsZSksXG4gICAgICBzZWxmLmNvb3Jkc1sxXSArIChoZWlnaHQgKiB5U2NhbGUpXG4gICAgKVxuICB9XG5cbiAgcmV0dXJuIHNlbGZcbn1cblxuLy8gQ29tYmluZSB3aXRoIGFub3RoZXIgYm91bmRzXG4vLyBAcmV0dXJucyB7Qm91bmRzfVxuQm91bmRzLnByb3RvdHlwZS5jb21iaW5lID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgdmFyIGFyZ3MgPSBBcnJheS5wcm90b3R5cGUuc2xpY2UuY2FsbChhcmd1bWVudHMpXG4gIHZhciB0b3RhbEJvdW5kcyA9IHNlbGYuY2xvbmUoKS5jb29yZHNcbiAgdmFyIG5ld0JvdW5kc1xuXG4gIGlmIChhcmdzLmxlbmd0aCA+IDApIHtcbiAgICAvLyBQcm9jZXNzIGVhY2ggaXRlbSBpbiB0aGUgYXJyYXlcbiAgICBmb3IgKHZhciBpID0gMDsgaSA8IGFyZ3MubGVuZ3RoOyBpKyspIHtcbiAgICAgIC8vIEJvdW5kcyBvYmplY3QgZ2l2ZW5cbiAgICAgIGlmIChhcmdzW2ldIGluc3RhbmNlb2YgQm91bmRzKSB7XG4gICAgICAgIG5ld0JvdW5kcyA9IGFyZ3NbaV1cblxuICAgICAgLy8gSFRNTEVsZW1lbnQsIFN0cmluZyBvciBBcnJheSBnaXZlbiAoeDEsIHkxLCB4MiwgeTIpXG4gICAgICB9IGVsc2UgaWYgKHR5cGVvZiBhcmdzW2ldID09PSAnc3RyaW5nJyB8fCBhcmdzW2ldIGluc3RhbmNlb2YgQXJyYXkgfHwgaXNFbGVtZW50KGFyZ3NbaV0pIHx8IGFyZ3NbMF0gPT09IHdpbmRvdykge1xuICAgICAgICBuZXdCb3VuZHMgPSBuZXcgQm91bmRzKGFyZ3NbaV0pXG4gICAgICB9XG5cbiAgICAgIC8vIENvbWJpbmVcbiAgICAgIGlmIChuZXdCb3VuZHMpIHtcbiAgICAgICAgZm9yICh2YXIgaiA9IDA7IGogPCBuZXdCb3VuZHMuY29vcmRzLmxlbmd0aDsgaisrKSB7XG4gICAgICAgICAgLy8gU2V0IGxvd2VzdC9oaWdoZXN0IHZhbHVlcyBvZiBib3VuZHNcbiAgICAgICAgICBpZiAoKGogPCAyICYmIG5ld0JvdW5kcy5jb29yZHNbal0gPCB0b3RhbEJvdW5kc1tqXSkgfHxcbiAgICAgICAgICAgICAgKGogPiAxICYmIG5ld0JvdW5kcy5jb29yZHNbal0gPiB0b3RhbEJvdW5kc1tqXSkpIHtcbiAgICAgICAgICAgIHRvdGFsQm91bmRzW2pdID0gbmV3Qm91bmRzLmNvb3Jkc1tqXVxuICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgfVxuICAgIH1cblxuICAgIC8vIFNldCBuZXcgY29tYmluZWQgYm91bmRzXG4gICAgcmV0dXJuIHNlbGYuc2V0Qm91bmRzKHRvdGFsQm91bmRzKVxuICB9XG59XG5cbi8vIFNldCBib3VuZHMgYmFzZWQgb24gYSBET00gZWxlbWVudFxuLy8gQHJldHVybnMge0JvdW5kc31cbkJvdW5kcy5wcm90b3R5cGUuc2V0Qm91bmRzRnJvbUVsZW0gPSBmdW5jdGlvbiAoZWxlbSkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgdmFyICRlbGVtXG4gIHZhciBlbGVtV2lkdGggPSAwXG4gIHZhciBlbGVtSGVpZ2h0ID0gMFxuICB2YXIgZWxlbU9mZnNldCA9IHtcbiAgICBsZWZ0OiAwLFxuICAgIHRvcDogMFxuICB9XG4gIHZhciBlbGVtQm91bmRzID0gWzAsIDAsIDAsIDBdXG4gIHZhciB3aW5kb3dPZmZzZXQgPSB7XG4gICAgbGVmdDogJCh3aW5kb3cpLnNjcm9sbExlZnQoKSxcbiAgICB0b3A6ICQod2luZG93KS5zY3JvbGxUb3AoKVxuICB9XG5cbiAgLy8gQ2xhcmlmeSBlbGVtIG9iamVjdHNcbiAgaWYgKCFlbGVtKSBlbGVtID0gc2VsZi5lbGVtXG4gIGlmICh0eXBlb2YgZWxlbSA9PT0gJ3VuZGVmaW5lZCcpIHJldHVybiBzZWxmXG4gICRlbGVtID0gJChlbGVtKVxuICBpZiAoJGVsZW0ubGVuZ3RoID09PSAwKSByZXR1cm4gc2VsZlxuICBzZWxmLmVsZW0gPSBlbGVtID0gJGVsZW1bMF1cblxuICAvLyBUcmVhdCB3aW5kb3cgb2JqZWN0IGRpZmZlcmVudGx5XG4gIGlmIChlbGVtID09PSB3aW5kb3cpIHtcbiAgICBlbGVtV2lkdGggPSAkKHdpbmRvdykuaW5uZXJXaWR0aCgpXG4gICAgZWxlbUhlaWdodCA9ICQod2luZG93KS5pbm5lckhlaWdodCgpXG4gICAgd2luZG93T2Zmc2V0LmxlZnQgPSAwXG4gICAgd2luZG93T2Zmc2V0LnRvcCA9IDBcblxuICAvLyBBbnkgb3RoZXIgZWxlbWVudFxuICB9IGVsc2Uge1xuICAgIGVsZW1XaWR0aCA9ICRlbGVtLm91dGVyV2lkdGgoKVxuICAgIGVsZW1IZWlnaHQgPSAkZWxlbS5vdXRlckhlaWdodCgpXG4gICAgZWxlbU9mZnNldCA9ICRlbGVtLm9mZnNldCgpXG4gIH1cblxuICAvLyBDYWxjdWxhdGUgdGhlIGJvdW5kc1xuICBlbGVtQm91bmRzID0gW1xuICAgIChlbGVtT2Zmc2V0LmxlZnQgLSB3aW5kb3dPZmZzZXQubGVmdCksXG4gICAgKGVsZW1PZmZzZXQudG9wIC0gd2luZG93T2Zmc2V0LnRvcCksXG4gICAgKGVsZW1PZmZzZXQubGVmdCArIGVsZW1XaWR0aCAtIHdpbmRvd09mZnNldC5sZWZ0KSxcbiAgICAoZWxlbU9mZnNldC50b3AgKyBlbGVtSGVpZ2h0IC0gd2luZG93T2Zmc2V0LnRvcClcbiAgXVxuXG4gIC8vIEBkZWJ1Z1xuICAvLyBzZWxmLnNob3dWaXooKVxuICAvLyBjb25zb2xlLmxvZygnQm91bmRzLnNldEJvdW5kc0Zyb21FbGVtJywge1xuICAvLyAgIGVsZW06IGVsZW0sXG4gIC8vICAgZWxlbUJvdW5kczogZWxlbUJvdW5kcyxcbiAgLy8gICBlbGVtV2lkdGg6IGVsZW1XaWR0aCxcbiAgLy8gICBlbGVtSGVpZ2h0OiBlbGVtSGVpZ2h0LFxuICAvLyAgIHdpbmRvd09mZnNldDogd2luZG93T2Zmc2V0XG4gIC8vIH0pXG5cbiAgLy8gSW5zdGVhZCBvZiBjcmVhdGluZyBhIG5ldyBib3VuZHMgb2JqZWN0LCBqdXN0IHVwZGF0ZSB0aGVzZSB2YWx1ZXNcbiAgc2VsZi5jb29yZHMgPSBlbGVtQm91bmRzXG4gIHNlbGYud2lkdGggPSBzZWxmLmdldFdpZHRoKClcbiAgc2VsZi5oZWlnaHQgPSBzZWxmLmdldEhlaWdodCgpXG5cbiAgLy8gU2V0IHRoZSBib3VuZHNcbiAgLy9yZXR1cm4gc2VsZi5zZXRCb3VuZHMoZWxlbUJvdW5kcylcbiAgcmV0dXJuIHNlbGZcbn1cblxuLy8gQ2hlY2sgaWYgY29vcmRzIG9yIGJvdW5kcyB3aXRoaW4gYW5vdGhlciBCb3VuZHMgb2JqZWN0XG4vLyBAcmV0dXJucyB7Qm9vbGVhbn1cbkJvdW5kcy5wcm90b3R5cGUud2l0aGluQm91bmRzID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgdmFyIGFyZ3MgPSBBcnJheS5wcm90b3R5cGUuc2xpY2UuY2FsbChhcmd1bWVudHMpXG4gIHZhciB0b3RhbEJvdW5kc1xuICB2YXIgdmlzaWJsZSA9IGZhbHNlXG5cbiAgLy8gQ2FsY3VsYXRlIHRoZSB0b3RhbCBib3VuZHNcbiAgZm9yICh2YXIgaSA9IDA7IGkgPCBhcmdzLmxlbmd0aDsgaSsrKSB7XG4gICAgdmFyIGFkZEJvdW5kc1xuICAgIC8vIEJvdW5kcyBvYmplY3RcbiAgICBpZiAoYXJnc1tpXSBpbnN0YW5jZW9mIEJvdW5kcykge1xuICAgICAgYWRkQm91bmRzID0gYXJnc1tpXVxuXG4gICAgLy8gQXJyYXkgb2JqZWN0XG4gICAgfSBlbHNlIGlmIChhcmdzW2ldIGluc3RhbmNlb2YgQXJyYXkpIHtcbiAgICAgIC8vIFNpbmdsZSBjby1vcmQgZ2l2ZW4gKHgsIHkpXG4gICAgICBpZiAoYXJnc1tpXS5sZW5ndGggPT09IDIpIHtcbiAgICAgICAgYWRkQm91bmRzID0gbmV3IEJvdW5kcyhhcmdzW2ldWzBdLCBhcmdzW2ldWzFdLCBhcmdzW2ldWzBdLCBhcmdzW2ldWzFdKVxuXG4gICAgICAvLyBQYWlyIG9mIGNvLW9yZHMgZ2l2ZW4gKHgxLCB5MSwgeDIsIHkyKVxuICAgICAgfSBlbHNlIGlmIChhcmdzW2ldLmxlbmd0aCA9PT0gNCkge1xuICAgICAgICBhZGRCb3VuZHMgPSBuZXcgQm91bmRzKGFyZ3NbaV0pXG4gICAgICB9XG5cbiAgICAvLyBTZWxlY3RvclxuICAgIH0gZWxzZSBpZiAodHlwZW9mIGFyZ3NbaV0gPT09ICdzdHJpbmcnKSB7XG4gICAgICBhZGRCb3VuZHMgPSBuZXcgQm91bmRzKCkuZ2V0Qm91bmRzRnJvbUVsZW0oYXJnc1tpXSlcbiAgICB9XG5cbiAgICAvLyBBZGQgdG8gdG90YWxcbiAgICBpZiAodG90YWxCb3VuZHMpIHtcbiAgICAgIHRvdGFsQm91bmRzLmNvbWJpbmUoYWRkQm91bmRzKVxuXG4gICAgLy8gQ3JlYXRlIG5ldyB0b3RhbFxuICAgIH0gZWxzZSB7XG4gICAgICB0b3RhbEJvdW5kcyA9IGFkZEJvdW5kc1xuICAgIH1cbiAgfVxuXG4gIC8vIEBkZWJ1Z1xuICAvLyB0b3RhbEJvdW5kcy5zaG93Vml6KClcblxuICAvLyBTZWUgaWYgdGhpcyBCb3VuZHMgaXMgd2l0aGluIHRoZSB0b3RhbEJvdW5kc1xuICB2aXNpYmxlID0gc2VsZi5jb29yZHNbMF0gPCB0b3RhbEJvdW5kcy5jb29yZHNbMl0gJiYgc2VsZi5jb29yZHNbMl0gPiB0b3RhbEJvdW5kcy5jb29yZHNbMF0gJiZcbiAgICAgICAgICAgIHNlbGYuY29vcmRzWzFdIDwgdG90YWxCb3VuZHMuY29vcmRzWzNdICYmIHNlbGYuY29vcmRzWzNdID4gdG90YWxCb3VuZHMuY29vcmRzWzFdXG5cbiAgcmV0dXJuIHZpc2libGVcbn1cblxuQm91bmRzLnByb3RvdHlwZS5nZXRDb29yZHNWaXNpYmxlV2l0aGluQm91bmRzID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgdmFyIGFyZ3MgPSBBcnJheS5wcm90b3R5cGUuc2xpY2UuY2FsbChhcmd1bWVudHMpXG4gIHZhciB0b3RhbEJvdW5kc1xuICB2YXIgY29vcmRzID0gW2ZhbHNlLCBmYWxzZSwgZmFsc2UsIGZhbHNlXVxuXG4gIC8vIENhbGN1bGF0ZSB0aGUgdG90YWwgYm91bmRzXG4gIGZvciAodmFyIGkgPSAwOyBpIDwgYXJncy5sZW5ndGg7IGkrKykge1xuICAgIHZhciBhZGRCb3VuZHNcbiAgICAvLyBCb3VuZHMgb2JqZWN0XG4gICAgaWYgKGFyZ3NbaV0gaW5zdGFuY2VvZiBCb3VuZHMpIHtcbiAgICAgIGFkZEJvdW5kcyA9IGFyZ3NbaV1cblxuICAgIC8vIEFycmF5IG9iamVjdFxuICAgIH0gZWxzZSBpZiAoYXJnc1tpXSBpbnN0YW5jZW9mIEFycmF5KSB7XG4gICAgICAvLyBTaW5nbGUgY28tb3JkIGdpdmVuICh4LCB5KVxuICAgICAgaWYgKGFyZ3NbaV0ubGVuZ3RoID09PSAyKSB7XG4gICAgICAgIGFkZEJvdW5kcyA9IG5ldyBCb3VuZHMoYXJnc1tpXVswXSwgYXJnc1tpXVsxXSwgYXJnc1tpXVswXSwgYXJnc1tpXVsxXSlcblxuICAgICAgLy8gUGFpciBvZiBjby1vcmRzIGdpdmVuICh4MSwgeTEsIHgyLCB5MilcbiAgICAgIH0gZWxzZSBpZiAoYXJnc1tpXS5sZW5ndGggPT09IDQpIHtcbiAgICAgICAgYWRkQm91bmRzID0gbmV3IEJvdW5kcyhhcmdzW2ldKVxuICAgICAgfVxuXG4gICAgLy8gU2VsZWN0b3JcbiAgICB9IGVsc2UgaWYgKHR5cGVvZiBhcmdzW2ldID09PSAnc3RyaW5nJykge1xuICAgICAgYWRkQm91bmRzID0gbmV3IEJvdW5kcygpLmdldEJvdW5kc0Zyb21FbGVtKGFyZ3NbaV0pXG4gICAgfVxuXG4gICAgLy8gQWRkIHRvIHRvdGFsXG4gICAgaWYgKHRvdGFsQm91bmRzKSB7XG4gICAgICB0b3RhbEJvdW5kcy5jb21iaW5lKGFkZEJvdW5kcylcblxuICAgIC8vIENyZWF0ZSBuZXcgdG90YWxcbiAgICB9IGVsc2Uge1xuICAgICAgdG90YWxCb3VuZHMgPSBhZGRCb3VuZHNcbiAgICB9XG4gIH1cblxuICAvLyBDaGVjayBlYWNoIGNvb3JkXG4gIGlmIChzZWxmLmNvb3Jkc1swXSA+PSB0b3RhbEJvdW5kcy5jb29yZHNbMF0gJiYgc2VsZi5jb29yZHNbMF0gPD0gdG90YWxCb3VuZHMuY29vcmRzWzJdKSBjb29yZHNbMF0gPSBzZWxmLmNvb3Jkc1swXVxuICBpZiAoc2VsZi5jb29yZHNbMV0gPj0gdG90YWxCb3VuZHMuY29vcmRzWzFdICYmIHNlbGYuY29vcmRzWzFdIDw9IHRvdGFsQm91bmRzLmNvb3Jkc1szXSkgY29vcmRzWzFdID0gc2VsZi5jb29yZHNbMV1cbiAgaWYgKHNlbGYuY29vcmRzWzJdID49IHRvdGFsQm91bmRzLmNvb3Jkc1swXSAmJiBzZWxmLmNvb3Jkc1syXSA8PSB0b3RhbEJvdW5kcy5jb29yZHNbMl0pIGNvb3Jkc1syXSA9IHNlbGYuY29vcmRzWzJdXG4gIGlmIChzZWxmLmNvb3Jkc1szXSA+PSB0b3RhbEJvdW5kcy5jb29yZHNbMV0gJiYgc2VsZi5jb29yZHNbM10gPD0gdG90YWxCb3VuZHMuY29vcmRzWzNdKSBjb29yZHNbM10gPSBzZWxmLmNvb3Jkc1szXVxuXG4gIHJldHVybiBjb29yZHNcbn1cblxuLy8gR2V0IHRoZSBvZmZzZXQgYmV0d2VlbiB0d28gYm91bmRzXG4vLyBAcmV0dXJucyB7Qm91bmRzfVxuQm91bmRzLnByb3RvdHlwZS5nZXRPZmZzZXRCZXR3ZWVuQm91bmRzID0gZnVuY3Rpb24gKGJvdW5kcykge1xuICB2YXIgc2VsZiA9IHRoaXNcblxuICB2YXIgb2Zmc2V0Q29vcmRzID0gW1xuICAgIHNlbGYuY29vcmRzWzBdIC0gYm91bmRzLmNvb3Jkc1swXSxcbiAgICBzZWxmLmNvb3Jkc1sxXSAtIGJvdW5kcy5jb29yZHNbMV0sXG4gICAgc2VsZi5jb29yZHNbMl0gLSBib3VuZHMuY29vcmRzWzJdLFxuICAgIHNlbGYuY29vcmRzWzNdIC0gYm91bmRzLmNvb3Jkc1szXVxuICBdXG5cbiAgcmV0dXJuIG5ldyBCb3VuZHMob2Zmc2V0Q29vcmRzKVxufVxuXG4vLyBDcmVhdGVzIGEgY29weSBvZiB0aGUgYm91bmRzXG4vLyBAcmV0dXJucyB7Qm91bmRzfVxuQm91bmRzLnByb3RvdHlwZS5jbG9uZSA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG4gIHJldHVybiBuZXcgQm91bmRzKHNlbGYuY29vcmRzKVxufVxuXG4vLyBUbyBzdHJpbmdcbi8vIEByZXR1cm5zIHtTdHJpbmd9XG5Cb3VuZHMucHJvdG90eXBlLnRvU3RyaW5nID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgcmV0dXJuIHNlbGYuY29vcmRzLmpvaW4oJywnKVxufVxuXG4vLyBCb3VuZHMgVmlzdWFsaXNlclxuQm91bmRzLnByb3RvdHlwZS5nZXRWaXpJZCA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG4gIHZhciBpZCA9IHNlbGYuaWRcbiAgaWYgKHNlbGYuZWxlbSkge1xuICAgIGlkID0gJChzZWxmLmVsZW0pLmF0dHIoJ2lkJykgfHwgKCQuaXNXaW5kb3coc2VsZi5lbGVtKSA/ICd3aW5kb3cnIDogc2VsZi5lbGVtLm5hbWUpIHx8IHNlbGYuaWRcbiAgfVxuICByZXR1cm4gJ2JvdW5kcy12aXotJyArIGlkXG59XG5cbkJvdW5kcy5wcm90b3R5cGUuZ2V0Vml6ID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcbiAgdmFyICRib3VuZHNWaXogPSAkKCcjJyArIHNlbGYuZ2V0Vml6SWQoKSlcbiAgcmV0dXJuICRib3VuZHNWaXpcbn1cblxuQm91bmRzLnByb3RvdHlwZS5zaG93Vml6ID0gZnVuY3Rpb24gKCkge1xuICB2YXIgc2VsZiA9IHRoaXNcblxuICAvLyBAZGVidWdcbiAgdmFyICRib3VuZHNWaXogPSBzZWxmLmdldFZpeigpXG4gIGlmICgkYm91bmRzVml6Lmxlbmd0aCA9PT0gMCkge1xuICAgICRib3VuZHNWaXogPSAkKCc8ZGl2IGlkPVwiJytzZWxmLmdldFZpeklkKCkrJ1wiIGNsYXNzPVwiYm91bmRzLXZpelwiPjwvZGl2PicpLmNzcyh7XG4gICAgICBwb3NpdGlvbjogJ2ZpeGVkJyxcbiAgICAgIGJhY2tncm91bmRDb2xvcjogWydyZWQnLCdncmVlbicsJ2JsdWUnLCd5ZWxsb3cnLCdvcmFuZ2UnXVtNYXRoLmZsb29yKE1hdGgucmFuZG9tKCkgKiA1KV0sXG4gICAgICBvcGFjaXR5OiAuMixcbiAgICAgIHpJbmRleDogOTk5OTk5OVxuICAgIH0pLmFwcGVuZFRvKCdib2R5JylcbiAgfVxuXG4gIC8vIFNldCAkdml6IGVsZW1lbnRcbiAgc2VsZi4kdml6ID0gJGJvdW5kc1ZpelxuXG4gIC8vIFVwZGF0ZSB2aXogcHJvcGVydGllc1xuICAkYm91bmRzVml6LmNzcyh7XG4gICAgbGVmdDogc2VsZi5jb29yZHNbMF0gKyAncHgnLFxuICAgIHRvcDogc2VsZi5jb29yZHNbMV0gKyAncHgnLFxuICAgIHdpZHRoOiBzZWxmLmdldFdpZHRoKCkgKyAncHgnLFxuICAgIGhlaWdodDogc2VsZi5nZXRIZWlnaHQoKSArICdweCdcbiAgfSlcblxuICByZXR1cm4gJGJvdW5kc1ZpelxufVxuXG5Cb3VuZHMucHJvdG90eXBlLnJlbW92ZVZpeiA9IGZ1bmN0aW9uICgpIHtcbiAgdmFyIHNlbGYgPSB0aGlzXG4gIHZhciAkYm91bmRzVml6ID0gc2VsZi5nZXRWaXooKVxuICBzZWxmLiR2aXogPSB1bmRlZmluZWRcbiAgJGJvdW5kc1Zpei5yZW1vdmUoKVxuICByZXR1cm4gc2VsZlxufVxuXG5tb2R1bGUuZXhwb3J0cyA9IEJvdW5kc1xuIiwiLy9cbi8vIFVuaWxlbmQgSlMgVGVtcGxhdGluZ1xuLy8gVmVyeSBiYXNpYyBzdHJpbmcgcmVwbGFjZW1lbnQgdG8gYWxsb3cgdGVtcGxhdGluZ1xuLy9cblxuZnVuY3Rpb24gcmVwbGFjZUtleXdvcmRzV2l0aFZhbHVlcyAoaW5wdXQsIHByb3BzKSB7XG4gIG91dHB1dCA9IGlucHV0XG4gIGlmICh0eXBlb2Ygb3V0cHV0ID09PSAndW5kZWZpbmVkJykgcmV0dXJuICcnXG5cbiAgLy8gU2VhcmNoIGZvciBrZXl3b3Jkc1xuICB2YXIgbWF0Y2hlcyA9IG91dHB1dC5tYXRjaCgvXFx7XFx7XFxzKlthLXowLTlfXFwtXFx8XStcXHMqXFx9XFx9L2dpKVxuICBpZiAobWF0Y2hlcy5sZW5ndGggPiAwKSB7XG4gICAgZm9yICh2YXIgaSA9IDA7IGkgPCBtYXRjaGVzLmxlbmd0aDsgaSsrKSB7XG4gICAgICB2YXIgcHJvcE5hbWUgPSBtYXRjaGVzW2ldLnJlcGxhY2UoL15cXHtcXHtcXHMqfFxccypcXH1cXH0kL2csICcnKVxuICAgICAgdmFyIHByb3BWYWx1ZSA9IChwcm9wcy5oYXNPd25Qcm9wZXJ0eShwcm9wTmFtZSkgPyBwcm9wc1twcm9wTmFtZV0gOiAnJylcblxuICAgICAgLy8gQGRlYnVnXG4gICAgICAvLyBjb25zb2xlLmxvZygnVGVtcGxhdGluZycsIG1hdGNoZXNbaV0sIHByb3BOYW1lLCBwcm9wVmFsdWUpXG5cbiAgICAgIC8vIFByb3AgaXMgZnVuY3Rpb25zXG4gICAgICAvLyBAbm90ZSBtYWtlIHN1cmUgY3VzdG9tIGZ1bmN0aW9ucyByZXR1cm4gdGhlaXIgZmluYWwgdmFsdWUgYXMgYSBzdHJpbmcgKG9yIHNvbWV0aGluZyBodW1hbi1yZWFkYWJsZSlcbiAgICAgIGlmICh0eXBlb2YgcHJvcFZhbHVlID09PSAnZnVuY3Rpb24nKSBwcm9wVmFsdWUgPSBwcm9wVmFsdWUuYXBwbHkocHJvcHMpXG5cbiAgICAgIG91dHB1dCA9IG91dHB1dC5yZXBsYWNlKG5ldyBSZWdFeHAobWF0Y2hlc1tpXSwgJ2cnKSwgcHJvcFZhbHVlKVxuICAgIH1cbiAgfVxuXG4gIHJldHVybiBvdXRwdXRcbn1cblxudmFyIFRlbXBsYXRpbmcgPSB7XG4gIC8vIFJlcGxhY2VzIGluc3RhbmNlcyBvZiB7eyBwcm9wTmFtZSB9fSBpbiB0aGUgdGVtcGxhdGUgc3RyaW5nIHdpdGggdGhlIGNvcnJlc3BvbmRpbmcgdmFsdWUgaW4gdGhlIHByb3BzIG9iamVjdFxuICByZXBsYWNlOiBmdW5jdGlvbiAoaW5wdXQsIHByb3BzKSB7XG4gICAgdmFyIG91dHB1dCA9IGlucHV0XG5cbiAgICAvLyBTdXBwb3J0IHByb2Nlc3NpbmcgcHJvcHMgaW4gc2VxdWVudGlhbCBvcmRlciB3aXRoIG11bHRpcGxlIG9iamVjdHNcbiAgICBpZiAoIShwcm9wcyBpbnN0YW5jZW9mIEFycmF5KSkgcHJvcHMgPSBbcHJvcHNdXG4gICAgZm9yICh2YXIgaSA9IDA7IGkgPCBwcm9wcy5sZW5ndGg7IGkrKykge1xuICAgICAgb3V0cHV0ID0gcmVwbGFjZUtleXdvcmRzV2l0aFZhbHVlcyhvdXRwdXQsIHByb3BzW2ldKVxuICAgIH1cblxuICAgIHJldHVybiBvdXRwdXRcbiAgfVxufVxuXG5tb2R1bGUuZXhwb3J0cyA9IFRlbXBsYXRpbmdcbiIsIi8vXG4vLyBUd2VlbmluZyBGdW5jdGlvbnNcbi8vIEBkZXNjcmlwdGlvbiBhZGFwdGVkIGZyb20gaHR0cDovL2dpem1hLmNvbS9lYXNpbmcvXG4vL1xuLy8gQHBhcmFtIHQgY3VycmVudCB0aW1lXG4vLyBAcGFyYW0gYiBzdGFydCB2YWx1ZVxuLy8gQHBhcmFtIGMgY2hhbmdlIGluIHZhbHVlXG4vLyBAcGFyYW0gZCBkdXJhdGlvblxuXG5tb2R1bGUuZXhwb3J0cyA9IHtcbiAgbGluZWFyVHdlZW46IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgcmV0dXJuIGMqdC9kICsgYlxuICB9LFxuXG4gIGVhc2VJblF1YWQ6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgdCAvPSBkXG4gICAgcmV0dXJuIGMqdCp0ICsgYlxuICB9LFxuXG4gIGVhc2VPdXRRdWFkOiBmdW5jdGlvbiAodCwgYiwgYywgZCkge1xuICAgIHQgLz0gZFxuICAgIHJldHVybiAtYyAqIHQqKHQtMikgKyBiXG4gIH0sXG5cbiAgZWFzZUluT3V0UXVhZDogZnVuY3Rpb24gKHQsIGIsIGMsIGQpIHtcbiAgICB0IC89IGQvMlxuICAgIGlmICh0IDwgMSkgcmV0dXJuIGMvMip0KnQgKyBiXG4gICAgdC0tXG4gICAgcmV0dXJuIC1jLzIgKiAodCoodC0yKSAtIDEpICsgYlxuICB9LFxuXG4gIGVhc2VJbkN1YmljOiBmdW5jdGlvbiAodCwgYiwgYywgZCkge1xuICAgIHQgLz0gZFxuICAgIHJldHVybiBjKnQqdCp0ICsgYlxuICB9LFxuXG4gIGVhc2VPdXRDdWJpYzogZnVuY3Rpb24gKHQsIGIsIGMsIGQpIHtcbiAgICB0IC89IGRcbiAgICB0LS1cbiAgICByZXR1cm4gYyoodCp0KnQgKyAxKSArIGJcbiAgfSxcblxuICBlYXNlSW5PdXRDdWJpYzogZnVuY3Rpb24gKHQsIGIsIGMsIGQpIHtcbiAgICB0IC89IGQvMlxuICAgIGlmICh0IDwgMSkgcmV0dXJuIGMvMip0KnQqdCArIGJcbiAgICB0IC09IDJcbiAgICByZXR1cm4gYy8yKih0KnQqdCArIDIpICsgYlxuICB9LFxuXG4gIGVhc2VJblF1YXJ0OiBmdW5jdGlvbiAodCwgYiwgYywgZCkge1xuICAgIHQgLz0gZFxuICAgIHJldHVybiBjKnQqdCp0KnQgKyBiXG4gIH0sXG5cbiAgZWFzZU91dFF1YXJ0OiBmdW5jdGlvbiAodCwgYiwgYywgZCkge1xuICAgIHQgLz0gZFxuICAgIHQtLVxuICAgIHJldHVybiAtYyAqICh0KnQqdCp0IC0gMSkgKyBiXG4gIH0sXG5cbiAgZWFzZUluT3V0UXVhcnQ6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgdCAvPSBkLzJcbiAgICBpZiAodCA8IDEpIHJldHVybiBjLzIqdCp0KnQqdCArIGJcbiAgICB0IC09IDJcbiAgICByZXR1cm4gLWMvMiAqICh0KnQqdCp0IC0gMikgKyBiXG4gIH0sXG5cbiAgZWFzZUluUXVpbnQ6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgdCAvPSBkXG4gICAgcmV0dXJuIGMqdCp0KnQqdCp0ICsgYlxuICB9LFxuXG4gIGVhc2VPdXRRdWludDogZnVuY3Rpb24gKHQsIGIsIGMsIGQpIHtcbiAgICB0IC89IGRcbiAgICB0LS1cbiAgICByZXR1cm4gYyoodCp0KnQqdCp0ICsgMSkgKyBiXG4gIH0sXG5cbiAgZWFzZUluT3V0UXVpbnQ6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgdCAvPSBkLzJcbiAgICBpZiAodCA8IDEpIHJldHVybiBjLzIqdCp0KnQqdCp0ICsgYlxuICAgIHQgLT0gMlxuICAgIHJldHVybiBjLzIqKHQqdCp0KnQqdCArIDIpICsgYlxuICB9LFxuXG4gIGVhc2VJblNpbmU6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgcmV0dXJuIC1jICogTWF0aC5jb3ModC9kICogKCBNYXRoLlBJLzIpKSArIGMgKyBiXG4gIH0sXG5cbiAgZWFzZU91dFNpbmU6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgcmV0dXJuIGMgKiBNYXRoLnNpbih0L2QgKiAoIE1hdGguUEkvMikpICsgYlxuICB9LFxuXG4gIGVhc2VJbk91dFNpbmU6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgcmV0dXJuIC1jLzIgKiAoICBjb3MoIE1hdGguUEkqdC9kKSAtIDEpICsgYlxuICB9LFxuXG4gIGVhc2VJbkV4cG86IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgcmV0dXJuIGMgKiBNYXRoLnBvdyggMiwgMTAgKiAodC9kIC0gMSkgKSArIGJcbiAgfSxcblxuICBlYXNlT3V0RXhwbzogZnVuY3Rpb24gKHQsIGIsIGMsIGQpIHtcbiAgICByZXR1cm4gYyAqICggLSBNYXRoLnBvdyggMiwgLTEwICogdC9kICkgKyAxICkgKyBiXG4gIH0sXG5cbiAgZWFzZUluT3V0RXhwbzogZnVuY3Rpb24gKHQsIGIsIGMsIGQpIHtcbiAgICB0IC89IGQvMlxuICAgIGlmICh0IDwgMSkgcmV0dXJuIGMvMiAqIE1hdGgucG93KCAyLCAxMCAqICh0IC0gMSkgKSArIGJcbiAgICB0LS1cbiAgICByZXR1cm4gYy8yICogKCAtIE1hdGgucG93KCAyLCAtMTAgKiB0KSArIDIgKSArIGJcbiAgfSxcblxuICBlYXNlSW5DaXJjOiBmdW5jdGlvbiAodCwgYiwgYywgZCkge1xuICAgIHQgLz0gZFxuICAgIHJldHVybiAtYyAqICggTWF0aC5zcXJ0KDEgLSB0KnQpIC0gMSkgKyBiXG4gIH0sXG5cbiAgZWFzZU91dENpcmM6IGZ1bmN0aW9uICh0LCBiLCBjLCBkKSB7XG4gICAgdCAvPSBkXG4gICAgdC0tXG4gICAgcmV0dXJuIGMgKiBNYXRoLnNxcnQoMSAtIHQqdCkgKyBiXG4gIH0sXG5cbiAgZWFzZUluT3V0Q2lyYzogZnVuY3Rpb24gKHQsIGIsIGMsIGQpIHtcbiAgICB0IC89IGQvMlxuICAgIGlmICh0IDwgMSkgcmV0dXJuIC1jLzIgKiAoIE1hdGguc3FydCgxIC0gdCp0KSAtIDEpICsgYlxuICAgIHQgLT0gMlxuICAgIHJldHVybiBjLzIgKiAoIE1hdGguc3FydCgxIC0gdCp0KSArIDEpICsgYlxuICB9XG59IiwiLypcbiAqIFV0aWxpdHkgRnVuY3Rpb25zXG4gKiBHZW5lcmFsIHNoYXJlZCBmdW5jdGlvbnMgYW5kIHByb3BlcnRpZXNcbiAqL1xuXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbClcblxudmFyIFV0aWxpdHkgPSB7XG4gIC8vIENsaWNrIGV2ZW50XG4gIGNsaWNrRXZlbnQ6ICQoJ2h0bWwnKS5pcygnLmhhcy10b3VjaGV2ZW50cycpID8gJ3RvdWNoZW5kJyA6ICdjbGljaycsXG5cbiAgLy8gVHJhbnNpdGlvbiBlbmQgZXZlbnRcbiAgdHJhbnNpdGlvbkVuZEV2ZW50OiAndHJhbnNpdGlvbmVuZCB3ZWJraXRUcmFuc2l0aW9uRW5kIG9UcmFuc2l0aW9uRW5kIG90cmFuc2l0aW9uZW5kJyxcblxuICAvLyBBbmltYXRpb24gZW5kIGV2ZW50XG4gIGFuaW1hdGlvbkVuZEV2ZW50OiAnYW5pbWF0aW9uZW5kIHdlYmtpdEFuaW1hdGlvbkVuZCBvQW5pbWF0aW9uRW5kIG9hbmltYXRpb25lbmQnLFxuXG4gIC8vIEdlbmVyYXRlIGEgcmFuZG9tIHN0cmluZ1xuICByYW5kb21TdHJpbmc6IGZ1bmN0aW9uIChzdHJpbmdMZW5ndGgpIHtcbiAgICB2YXIgb3V0cHV0ID0gJydcbiAgICB2YXIgY2hhcnMgPSAnYWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXpBQkNERUZHSElKS0xNTk9QUVJTVFVWV1hZWidcbiAgICBzdHJpbmdMZW5ndGggPSBzdHJpbmdMZW5ndGggfHwgOFxuICAgIGZvciAodmFyIGkgPSAwOyBpIDwgc3RyaW5nTGVuZ3RoOyBpKyspIHtcbiAgICAgIG91dHB1dCArPSBjaGFycy5jaGFyQXQoTWF0aC5mbG9vcihNYXRoLnJhbmRvbSgpICogY2hhcnMubGVuZ3RoKSlcbiAgICB9XG4gICAgcmV0dXJuIG91dHB1dFxuICB9LFxuXG4gIC8vIENvbnZlcnQgYW4gaW5wdXQgdmFsdWUgKG1vc3QgbGlrZWx5IGEgc3RyaW5nKSBpbnRvIGEgcHJpbWl0aXZlLCBlLmcuIG51bWJlciwgYm9vbGVhbiwgZXRjLlxuICBjb252ZXJ0VG9QcmltaXRpdmU6IGZ1bmN0aW9uIChpbnB1dCkge1xuICAgIC8vIE5vbi1zdHJpbmc/IEp1c3QgcmV0dXJuIGl0IHN0cmFpZ2h0IGF3YXlcbiAgICBpZiAodHlwZW9mIGlucHV0ICE9PSAnc3RyaW5nJykgcmV0dXJuIGlucHV0XG5cbiAgICAvLyBUcmltIGFueSB3aGl0ZXNwYWNlXG4gICAgaW5wdXQgPSAoaW5wdXQgKyAnJykudHJpbSgpXG5cbiAgICAvLyBOdW1iZXJcbiAgICBpZiAoL15cXC0/KD86XFxkKltcXC5cXCxdKSpcXGQqKD86W2VFXSg/OlxcLT9cXGQrKT8pPyQvLnRlc3QoaW5wdXQpKSB7XG4gICAgICByZXR1cm4gcGFyc2VGbG9hdChpbnB1dClcbiAgICB9XG5cbiAgICAvLyBCb29sZWFuOiB0cnVlXG4gICAgaWYgKC9eKHRydWV8MSkkLy50ZXN0KGlucHV0KSkge1xuICAgICAgcmV0dXJuIHRydWVcblxuICAgIC8vIE5hTlxuICAgIH0gZWxzZSBpZiAoL15OYU4kLy50ZXN0KGlucHV0KSkge1xuICAgICAgcmV0dXJuIE5hTlxuXG4gICAgLy8gdW5kZWZpbmVkXG4gICAgfSBlbHNlIGlmICgvXnVuZGVmaW5lZCQvLnRlc3QoaW5wdXQpKSB7XG4gICAgICByZXR1cm4gdW5kZWZpbmVkXG5cbiAgICAvLyBudWxsXG4gICAgfSBlbHNlIGlmICgvXm51bGwkLy50ZXN0KGlucHV0KSkge1xuICAgICAgcmV0dXJuIG51bGxcblxuICAgIC8vIEJvb2xlYW46IGZhbHNlXG4gICAgfSBlbHNlIGlmICgvXihmYWxzZXwwKSQvLnRlc3QoaW5wdXQpIHx8IGlucHV0ID09PSAnJykge1xuICAgICAgcmV0dXJuIGZhbHNlXG4gICAgfVxuXG4gICAgLy8gRGVmYXVsdCB0byBzdHJpbmdcbiAgICByZXR1cm4gaW5wdXRcbiAgfSxcblxuICBjaGVja0VsZW1BdHRyRm9yVmFsdWU6IGZ1bmN0aW9uIChlbGVtLCBhdHRyKSB7XG4gICAgdmFyICRlbGVtID0gJChlbGVtKVxuICAgIHZhciBhdHRyVmFsdWUgPSAkZWxlbS5hdHRyKGF0dHIpXG5cbiAgICAvLyBDaGVjayBub24tJ2RhdGEtJyBwcmVmaXhlZCBhdHRyaWJ1dGVzIGZvciBvbmUgaWYgdmFsdWUgaXMgdW5kZWZpbmVkXG4gICAgaWYgKHR5cGVvZiBhdHRyVmFsdWUgPT09ICd1bmRlZmluZWQnICYmICEvXmRhdGFcXC0vaS50ZXN0KGF0dHIpKSB7XG4gICAgICBhdHRyVmFsdWUgPSAkZWxlbS5hdHRyKCdkYXRhLScgKyBhdHRyKVxuICAgIH1cblxuICAgIHJldHVybiBhdHRyVmFsdWVcbiAgfSxcblxuICAvLyBDaGVjayBpZiB0aGUgZWxlbWVudCBpcyBvciBpdHMgcGFyZW50cycgbWF0Y2hlcyBhIHtTdHJpbmd9IHNlbGVjdG9yXG4gIC8vIEByZXR1cm5zIHtCb29sZWFufVxuICBjaGVja0VsZW1Jc09ySGFzUGFyZW50OiBmdW5jdGlvbiAoZWxlbSwgc2VsZWN0b3IpIHtcbiAgICByZXR1cm4gJChlbGVtKS5pcyhzZWxlY3RvcikgfHwgJChlbGVtKS5wYXJlbnRzKHNlbGVjdG9yKS5sZW5ndGggPiAwXG4gIH0sXG5cbiAgLy8gU2FtZSBhcyBhYm92ZSwgZXhjZXB0IHJldHVybnMgdGhlIGVsZW1lbnRzIGl0c2VsZlxuICAvLyBAcmV0dXJucyB7TWl4ZWR9IEEge2pRdWVyeU9iamVjdH0gY29udGFpbmluZyB0aGUgZWxlbWVudChzKSwgb3Ige0Jvb2xlYW59IGZhbHNlXG4gIGdldEVsZW1Jc09ySGFzUGFyZW50OiBmdW5jdGlvbiAoZWxlbSwgc2VsZWN0b3IpIHtcbiAgICB2YXIgJGVsZW0gPSAkKGVsZW0pXG4gICAgaWYgKCRlbGVtLmlzKHNlbGVjdG9yKSkgcmV0dXJuICRlbGVtXG5cbiAgICB2YXIgJHBhcmVudHMgPSAkZWxlbS5wYXJlbnRzKHNlbGVjdG9yKVxuICAgIGlmICgkcGFyZW50cy5sZW5ndGggPiAwKSByZXR1cm4gJHBhcmVudHNcblxuICAgIHJldHVybiBmYWxzZVxuICB9LFxuXG4gIC8vIEFkZCBsZWFkaW5nIHplcm9cbiAgbGVhZGluZ1plcm86IGZ1bmN0aW9uIChpbnB1dCkge1xuICAgIHJldHVybiAocGFyc2VJbnQoaW5wdXQsIDEwKSA8IDEwID8gJzAnIDogJycpICsgaW5wdXRcbiAgfSxcblxuICAvLyBHZXQgdGhlIHJlbWFpbmluZyB0aW1lIGJldHdlZW4gdHdvIGRhdGVzXG4gIC8vIEBub3RlIFRpbWVDb3VudCByZWxpZXMgb24gdGhpcyB0byBvdXRwdXQgYXMgYW4gb2JqZWN0XG4gIC8vIFNlZTogaHR0cDovL3d3dy5zaXRlcG9pbnQuY29tL2J1aWxkLWphdmFzY3JpcHQtY291bnRkb3duLXRpbWVyLW5vLWRlcGVuZGVuY2llcy9cbiAgZ2V0VGltZVJlbWFpbmluZzogZnVuY3Rpb24gKGVuZFRpbWUsIHN0YXJ0VGltZSkge1xuICAgIHZhciB0ID0gRGF0ZS5wYXJzZShlbmRUaW1lKSAtIERhdGUucGFyc2Uoc3RhcnRUaW1lIHx8IG5ldyBEYXRlKCkpXG4gICAgdmFyIHNlY29uZHMgPSBNYXRoLmZsb29yKCh0LzEwMDApICUgNjApXG4gICAgdmFyIG1pbnV0ZXMgPSBNYXRoLmZsb29yKCh0LzEwMDAvNjApICUgNjApXG4gICAgdmFyIGhvdXJzID0gTWF0aC5mbG9vcigodC8oMTAwMCo2MCo2MCkpICUgMjQpXG4gICAgdmFyIGRheXMgPSBNYXRoLmZsb29yKHQvKDEwMDAqNjAqNjAqMjQpKVxuICAgIHJldHVybiB7XG4gICAgICAndG90YWwnOiB0LFxuICAgICAgJ2RheXMnOiBkYXlzLFxuICAgICAgJ2hvdXJzJzogaG91cnMsXG4gICAgICAnbWludXRlcyc6IG1pbnV0ZXMsXG4gICAgICAnc2Vjb25kcyc6IHNlY29uZHNcbiAgICB9XG4gIH1cbn1cblxubW9kdWxlLmV4cG9ydHMgPSBVdGlsaXR5XG4iLCIvKlxuICogVW5pbGVuZCBXYXRjaCBTY3JvbGxcbiAqXG4gKiBTZXQgYW5kIG1hbmFnZSBjYWxsYmFja3MgdG8gb2NjdXIgb24gZWxlbWVudCdzIHNjcm9sbCB0b3AvbGVmdCB2YWx1ZVxuICpcbiAqIEBsaW50ZXIgU3RhbmRhcmQgSlMgKGh0dHA6Ly9zdGFuZGFyZGpzLmNvbS8pXG4gKi9cblxuLy8gV2F0Y2ggZWxlbWVudCBmb3Igc2Nyb2xsIGxlZnQvdG9wIGFuZCBwZXJmb3JtIGNhbGxiYWNrIGlmOlxuLy8gLS0gSWYgZWxlbWVudCByZWFjaGVzIHNjcm9sbCBsZWZ0L3RvcCB2YWx1ZVxuLy8gLS0gSWYgc3BlY2lmaWMgY2hpbGQgZWxlbWVudCBpbiBlbGVtZW50IGlzIGVudGVyZWQgb3IgaXMgdmlzaWJsZSBpbiB2aWV3cG9ydFxuLy8gLS0gSWYgc3BlY2lmaWMgY2hpbGQgZWxlbWVudCBpbiBlbGVtZW50IGlzIGxlZnQgb3Igbm90IHZpc2libGUgaW4gdmlld3BvcnRcblxuLy8gRGVwZW5kZW5jaWVzXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbClcbnZhciBCb3VuZHMgPSByZXF1aXJlKCdFbGVtZW50Qm91bmRzJylcblxuLy8gcmVxdWVzdEFuaW1hdGlvbkZyYW1lIHBvbHlmaWxsXG4vLyBTZWU6IGh0dHA6Ly9jcmVhdGl2ZWpzLmNvbS9yZXNvdXJjZXMvcmVxdWVzdGFuaW1hdGlvbmZyYW1lL1xuOyhmdW5jdGlvbigpIHtcbiAgdmFyIGxhc3RUaW1lID0gMFxuICB2YXIgdmVuZG9ycyA9IFsnbXMnLCAnbW96JywgJ3dlYmtpdCcsICdvJ11cbiAgZm9yKHZhciB4ID0gMDsgeCA8IHZlbmRvcnMubGVuZ3RoICYmICF3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lOyArK3gpIHtcbiAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lID0gd2luZG93W3ZlbmRvcnNbeF0rJ1JlcXVlc3RBbmltYXRpb25GcmFtZSddXG4gICAgd2luZG93LmNhbmNlbEFuaW1hdGlvbkZyYW1lID0gd2luZG93W3ZlbmRvcnNbeF0rJ0NhbmNlbEFuaW1hdGlvbkZyYW1lJ11cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB8fCB3aW5kb3dbdmVuZG9yc1t4XSsnQ2FuY2VsUmVxdWVzdEFuaW1hdGlvbkZyYW1lJ11cbiAgfVxuXG4gIGlmICghd2luZG93LnJlcXVlc3RBbmltYXRpb25GcmFtZSlcbiAgICB3aW5kb3cucmVxdWVzdEFuaW1hdGlvbkZyYW1lID0gZnVuY3Rpb24oY2FsbGJhY2ssIGVsZW1lbnQpIHtcbiAgICAgIHZhciBjdXJyVGltZSA9IG5ldyBEYXRlKCkuZ2V0VGltZSgpXG4gICAgICB2YXIgdGltZVRvQ2FsbCA9IE1hdGgubWF4KDAsIDE2IC0gKGN1cnJUaW1lIC0gbGFzdFRpbWUpKVxuICAgICAgdmFyIGlkID0gd2luZG93LnNldFRpbWVvdXQoZnVuY3Rpb24oKSB7IGNhbGxiYWNrKGN1cnJUaW1lICsgdGltZVRvQ2FsbCkgfSxcbiAgICAgICAgdGltZVRvQ2FsbClcbiAgICAgIGxhc3RUaW1lID0gY3VyclRpbWUgKyB0aW1lVG9DYWxsXG4gICAgICByZXR1cm4gaWRcbiAgICB9XG5cbiAgaWYgKCF3aW5kb3cuY2FuY2VsQW5pbWF0aW9uRnJhbWUpXG4gICAgd2luZG93LmNhbmNlbEFuaW1hdGlvbkZyYW1lID0gZnVuY3Rpb24oaWQpIHtcbiAgICAgIGNsZWFyVGltZW91dChpZClcbiAgICB9XG59KCkpXG5cbi8qXG4gKiBXYXRjaFNjcm9sbFxuICovXG52YXIgV2F0Y2hTY3JvbGwgPSB7XG4gIC8qXG4gICAqIEFjdGlvbnMgdG8gdGVzdCBXYXRjaGVyIGVsZW1lbnRzIGFuZCB0YXJnZXRzIHdpdGhcbiAgICogQG5vdGUgU2VlIFdhdGNoZXIuY2hlY2tUYXJnZXRGb3JBY3Rpb24oKSB0byBzZWUgaG93IHRoZXNlIGdldCBhcHBsaWVkXG4gICAqIEBwcm9wZXJ0eVxuICAgKi9cbiAgYWN0aW9uczoge1xuICAgIC8vIENoZWNrcyB0byBzZWUgaWYgdGhlIHRhcmdldCBpcyBvdXRzaWRlIHRoZSBlbGVtZW50XG4gICAgb3V0c2lkZTogZnVuY3Rpb24gKHBhcmFtcykge1xuICAgICAgdmFyIGVsZW1Cb3VuZHMgPSBuZXcgQm91bmRzKCkuc2V0Qm91bmRzRnJvbUVsZW0ocGFyYW1zLldhdGNoZXIuZWxlbSlcbiAgICAgIHZhciB0YXJnZXRCb3VuZHMgPSBuZXcgQm91bmRzKCkuc2V0Qm91bmRzRnJvbUVsZW0odGhpcylcbiAgICAgIHZhciBzdGF0ZSA9IHRhcmdldEJvdW5kcy53aXRoaW5Cb3VuZHMoZWxlbUJvdW5kcylcbiAgICAgIGVsZW1Cb3VuZHMuc2hvd1ZpeigpXG4gICAgICB0YXJnZXRCb3VuZHMuc2hvd1ZpeigpXG4gICAgICBpZiAoIXN0YXRlKSByZXR1cm4gJ291dHNpZGUnXG4gICAgfSxcblxuICAgIC8vIENoZWNrcyB0byBzZWUgaWYgdGhlIHRhcmdldCBpcyBiZWZvcmUgdGhlIGVsZW1lbnQgKFggYXhpcylcbiAgICBiZWZvcmU6IGZ1bmN0aW9uIChwYXJhbXMpIHtcbiAgICAgIHZhciBlbGVtQm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHBhcmFtcy5XYXRjaGVyLmVsZW0pXG4gICAgICB2YXIgdGFyZ2V0Qm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHRoaXMpXG4gICAgICB2YXIgc3RhdGUgPSB0YXJnZXRCb3VuZHMuY29vcmRzWzJdIDwgZWxlbUJvdW5kcy5jb29yZHNbMF1cbiAgICAgIGVsZW1Cb3VuZHMuc2hvd1ZpeigpXG4gICAgICB0YXJnZXRCb3VuZHMuc2hvd1ZpeigpXG4gICAgICBpZiAoc3RhdGUpIHJldHVybiAnYmVmb3JlJ1xuICAgIH0sXG5cbiAgICAvLyBDaGVja3MgdG8gc2VlIGlmIHRoZSB0YXJnZXQgaXMgYWZ0ZXIgdGhlIGVsZW1lbnQgKFggYXhpcylcbiAgICBhZnRlcjogZnVuY3Rpb24gKHBhcmFtcykge1xuICAgICAgdmFyIGVsZW1Cb3VuZHMgPSBuZXcgQm91bmRzKCkuc2V0Qm91bmRzRnJvbUVsZW0ocGFyYW1zLldhdGNoZXIuZWxlbSlcbiAgICAgIHZhciB0YXJnZXRCb3VuZHMgPSBuZXcgQm91bmRzKCkuc2V0Qm91bmRzRnJvbUVsZW0odGhpcylcbiAgICAgIHZhciBzdGF0ZSA9IHRhcmdldEJvdW5kcy5jb29yZHNbMF0gPiBlbGVtQm91bmRzLmNvb3Jkc1syXVxuICAgICAgZWxlbUJvdW5kcy5zaG93Vml6KClcbiAgICAgIHRhcmdldEJvdW5kcy5zaG93Vml6KClcbiAgICAgIGlmIChzdGF0ZSkgcmV0dXJuICdhZnRlcidcbiAgICB9LFxuXG4gICAgLy8gQ2hlY2tzIHRvIHNlZSBpZiB0aGUgdGFyZ2V0IGlzIGFib3ZlIHRoZSBlbGVtZW50IChZIGF4aXMpXG4gICAgYWJvdmU6IGZ1bmN0aW9uIChwYXJhbXMpIHtcbiAgICAgIHZhciBlbGVtQm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHBhcmFtcy5XYXRjaGVyLmVsZW0pXG4gICAgICB2YXIgdGFyZ2V0Qm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHRoaXMpXG4gICAgICAvLyB0YXJnZXQuWTIgPCBlbGVtLlkxXG4gICAgICB2YXIgc3RhdGUgPSB0YXJnZXRCb3VuZHMuY29vcmRzWzNdIDwgZWxlbUJvdW5kcy5jb29yZHNbMV1cbiAgICAgIGVsZW1Cb3VuZHMuc2hvd1ZpeigpXG4gICAgICB0YXJnZXRCb3VuZHMuc2hvd1ZpeigpXG4gICAgICBpZiAoc3RhdGUpIHJldHVybiAnYWJvdmUnXG4gICAgfSxcblxuICAgIC8vIENoZWNrcyB0byBzZWUgaWYgdGhlIHRhcmdldCBpcyBiZWxvdyB0aGUgZWxlbWVudCAoWSBheGlzKVxuICAgIGJlbG93OiBmdW5jdGlvbiAocGFyYW1zKSB7XG4gICAgICB2YXIgZWxlbUJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbShwYXJhbXMuV2F0Y2hlci5lbGVtKVxuICAgICAgdmFyIHRhcmdldEJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbSh0aGlzKVxuICAgICAgLy8gdGFyZ2V0LlkxID4gZWxlbS5ZMlxuICAgICAgdmFyIHN0YXRlID0gdGFyZ2V0Qm91bmRzLmNvb3Jkc1sxXSA+IGVsZW1Cb3VuZHMuY29vcmRzWzNdXG4gICAgICBpZiAoc3RhdGUpIHJldHVybiAnYmVsb3cnXG4gICAgfSxcblxuICAgIC8vIENoZWNrcyBpZiB0aGUgdGFyZ2V0IGlzIHBhc3QgdGhlIGVsZW1lbnQgKFkgYXhpcylcbiAgICBwYXN0OiBmdW5jdGlvbiAocGFyYW1zKSB7XG4gICAgICB2YXIgZWxlbUJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbShwYXJhbXMuV2F0Y2hlci5lbGVtKVxuICAgICAgdmFyIHRhcmdldEJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbSh0aGlzKVxuICAgICAgLy8gdGFyZ2V0LlkxID4gZWxlbS5ZMVxuICAgICAgdmFyIHN0YXRlID0gdGFyZ2V0Qm91bmRzLmNvb3Jkc1sxXSA+IGVsZW1Cb3VuZHMuY29vcmRzWzFdXG4gICAgICBlbGVtQm91bmRzLnNob3dWaXooKVxuICAgICAgdGFyZ2V0Qm91bmRzLnNob3dWaXooKVxuICAgICAgaWYgKHN0YXRlKSByZXR1cm4gJ3Bhc3QnXG4gICAgfSxcblxuICAgIC8vIENoZWNrcyB0byBzZWUgaWYgdGhlIHRhcmdldCBpcyB3aXRoaW4gdGhlIGVsZW1lbnRcbiAgICB3aXRoaW46IGZ1bmN0aW9uIChwYXJhbXMpIHtcbiAgICAgIHZhciBlbGVtQm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHBhcmFtcy5XYXRjaGVyLmVsZW0pXG4gICAgICB2YXIgdGFyZ2V0Qm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHRoaXMpXG4gICAgICB2YXIgc3RhdGUgPSB0YXJnZXRCb3VuZHMud2l0aGluQm91bmRzKGVsZW1Cb3VuZHMpXG4gICAgICBpZiAoc3RhdGUpIHJldHVybiAnd2l0aGluJ1xuICAgIH0sXG5cbiAgICAvLyBDaGVja3MgdG8gc2VlIGlmIHRoZSB0YXJnZXQgaXMgaW4gdG9wIGhhbGYgb2YgdGhlIGVsZW1lbnRcbiAgICB3aXRoaW5Ub3BIYWxmOiBmdW5jdGlvbiAocGFyYW1zKSB7XG4gICAgICB2YXIgZWxlbUJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbShwYXJhbXMuV2F0Y2hlci5lbGVtKS5zY2FsZSgxLCAwLjUpXG4gICAgICB2YXIgdGFyZ2V0Qm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHRoaXMpXG4gICAgICB2YXIgc3RhdGUgPSB0YXJnZXRCb3VuZHMud2l0aGluQm91bmRzKGVsZW1Cb3VuZHMpXG4gICAgICBpZiAoc3RhdGUpIHJldHVybiAnd2l0aGludG9waGFsZidcbiAgICB9LFxuXG4gICAgLy8gQ2hlY2tzIHRvIHNlZSBpZiB0YXJnZXQgaXMgaW4gdGhlIG1pZGRsZSBvZiB0aGUgZWxlbWVudFxuICAgIHdpdGhpbk1pZGRsZTogZnVuY3Rpb24gKHBhcmFtcykge1xuICAgICAgLy8gR2V0IHRoZSBib3VuZHMgb2YgYWxsXG4gICAgICB2YXIgZWxlbUJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbShwYXJhbXMuV2F0Y2hlci5lbGVtKVxuICAgICAgdmFyIHRhcmdldEJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbSh0aGlzKVxuXG4gICAgICAvLyBHZXQgbWlkZGxlIG9mIGVsZW1cbiAgICAgIHZhciBtaWRkbGVZMSA9IChlbGVtQm91bmRzLmdldEhlaWdodCgpICogMC41KSAtIDFcbiAgICAgIHZhciBtaWRkbGVZMiA9IChlbGVtQm91bmRzLmdldEhlaWdodCgpICogMC41KVxuICAgICAgdmFyIG1pZGRsZUJvdW5kcyA9IG5ldyBCb3VuZHMoZWxlbUJvdW5kcy5jb29yZHNbMF0sIG1pZGRsZVkxLCBlbGVtQm91bmRzLmNvb3Jkc1syXSwgbWlkZGxlWTIpXG5cbiAgICAgIC8vIElzIHRhcmdldCB3aXRoaW4gbWlkZGxlP1xuICAgICAgdmFyIHN0YXRlID0gdGFyZ2V0Qm91bmRzLndpdGhpbkJvdW5kcyhtaWRkbGVCb3VuZHMpXG4gICAgICBpZiAoc3RhdGUpIHJldHVybiAnd2l0aGlubWlkZGxlJ1xuICAgIH1cbiAgfSxcblxuICAvKlxuICAgKiBXYXRjaFNjcm9sbC5XYXRjaGVyXG4gICAqIFdhdGNoZXMgYW4gZWxlbWVudCB3aXRoIGEgbGlzdCBvZiBsaXN0ZW5lcnMgYW5kIHRhcmdldHNcbiAgICogQGNsYXNzXG4gICAqIEBwYXJhbSBlbGVtIHtTdHJpbmcgfCBIVE1MRWxlbWVudH0gVGhlIGVsZW1lbnQgdG8gd2F0Y2ggc2Nyb2xsIHBvc2l0aW9uc1xuICAgKiBAcGFyYW0gb3B0aW9ucyB7T2JqZWN0fSBUaGUgb3B0aW9ucyB0byBjb25maWd1cmUgdGhlIHdhdGNoZXJcbiAgICovXG4gIFdhdGNoZXI6IGZ1bmN0aW9uIChlbGVtLCBvcHRpb25zKSB7XG4gICAgdmFyIHNlbGYgPSB0aGlzXG5cbiAgICAvKlxuICAgICAqIFByb3BlcnRpZXNcbiAgICAgKi9cbiAgICBzZWxmLiRlbGVtID0gJChlbGVtKSAvLyBqUXVlcnlcbiAgICBzZWxmLmVsZW0gPSBzZWxmLiRlbGVtWzBdIC8vIE5vcm1hbCBIVE1MRWxlbWVudFxuICAgIHNlbGYubGlzdGVuZXJzID0gW10gLy8gTGlzdCBvZiBsaXN0ZW5lcnNcblxuICAgIC8qXG4gICAgICogT3B0aW9uc1xuICAgICAqL1xuICAgIHNlbGYub3B0aW9ucyA9ICQuZXh0ZW5kKHtcbiAgICAgIC8vIE5vdGhpbmcgeWV0XG4gICAgfSwgb3B0aW9ucylcblxuICAgIC8qXG4gICAgICogTWV0aG9kc1xuICAgICAqL1xuICAgIC8vIEFkZCBhIGxpc3RlbmVyIHRvIHdhdGNoIGEgdGFyZ2V0IGVsZW1lbnQgKG9yIGNvbGxlY3Rpb24gb2YgZWxlbWVudHMpXG4gICAgc2VsZi53YXRjaCA9IGZ1bmN0aW9uICh0YXJnZXQsIGFjdGlvbiwgY2FsbGJhY2spIHtcbiAgICAgIC8vIE5lZWRzIGEgdmFsaWQgdGFyZ2V0LCBhY3Rpb24gYW5kIGNhbGxiYWNrXG4gICAgICBpZiAodHlwZW9mIHRhcmdldCAhPT0gJ29iamVjdCcgJiYgdHlwZW9mIHRhcmdldCAhPT0gJ3N0cmluZycpIHJldHVyblxuICAgICAgaWYgKHR5cGVvZiBhY3Rpb24gIT09ICdzdHJpbmcnICYmIHR5cGVvZiBhY3Rpb24gIT09ICdmdW5jdGlvbicpIHJldHVyblxuICAgICAgLy8gaWYgKHR5cGVvZiBjYWxsYmFjayAhPT0gJ2Z1bmN0aW9uJykgcmV0dXJuXG5cbiAgICAgIC8vIENyZWF0ZSB0aGUgV2F0Y2hTY3JvbGxMaXN0ZW5lclxuICAgICAgdmFyIHdhdGNoU2Nyb2xsTGlzdGVuZXIgPSBuZXcgV2F0Y2hTY3JvbGwuTGlzdGVuZXIodGFyZ2V0LCBhY3Rpb24sIGNhbGxiYWNrKVxuICAgICAgd2F0Y2hTY3JvbGxMaXN0ZW5lci5XYXRjaFNjcm9sbFdhdGNoZXIgPSBzZWxmXG5cbiAgICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnV2F0Y2hTY3JvbGwud2F0Y2gnLCB0YXJnZXQsIGFjdGlvbilcblxuICAgICAgLy8gRmlyZSBhbnkgcmVsZXZhbnQgYWN0aW9ucyBvbiB0aGUgbmV3bHkgd2F0Y2hlZCB0YXJnZXRcbiAgICAgIHdhdGNoU2Nyb2xsTGlzdGVuZXIuJHRhcmdldC5lYWNoKCBmdW5jdGlvbiAoaSwgdGFyZ2V0KSB7XG4gICAgICAgIGZvciAodmFyIGkgPSAwOyBpIDwgd2F0Y2hTY3JvbGxMaXN0ZW5lci5hY3Rpb24ubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgICB2YXIgZG9uZUFjdGlvbiA9IHNlbGYuY2hlY2tUYXJnZXRGb3JBY3Rpb24odGFyZ2V0LCB3YXRjaFNjcm9sbExpc3RlbmVyLmFjdGlvbltpXSwgd2F0Y2hTY3JvbGxMaXN0ZW5lci5jYWxsYmFjaylcbiAgICAgICAgfVxuICAgICAgfSlcblxuICAgICAgLy8gRW5hYmxlIHdhdGNoaW5nXG4gICAgICBpZiAod2F0Y2hTY3JvbGxMaXN0ZW5lcikgc2VsZi5saXN0ZW5lcnMucHVzaCh3YXRjaFNjcm9sbExpc3RlbmVyKVxuXG4gICAgICAvLyBZb3UgY2FuIGNoYWluIG1vcmUgd2F0Y2hlcnMgdG8gdGhlIGluc3RhbmNlXG4gICAgICByZXR1cm4gc2VsZlxuICAgIH1cblxuICAgIC8vIEdldCB0aGUgYm91bmRzIG9mIGFuIGVsZW1lbnRcbiAgICBzZWxmLmdldEJvdW5kcyA9IGZ1bmN0aW9uICh0YXJnZXQpIHtcbiAgICAgIHZhciB0YXJnZXRCb3VuZHMgPSBuZXcgQm91bmRzKCkuc2V0Qm91bmRzRnJvbUVsZW0odGFyZ2V0KS5jb29yZHNcbiAgICAgIHJldHVybiB0YXJnZXRCb3VuZHNcbiAgICB9XG5cbiAgICAvLyBDaGVjayBpZiBhIHNwYWNlIChkZW5vdGVkIGVpdGhlciBieSBhbiBlbGVtZW50LCBvciBieSAyIHNldHMgb2YgeC95IGNvLW9yZHMpIGlzIHZpc2libGUgd2l0aGluIHRoZSBlbGVtZW50XG4gICAgc2VsZi5pc1Zpc2libGUgPSBmdW5jdGlvbiAodGFyZ2V0KSB7XG4gICAgICB2YXIgZWxlbUJvdW5kcyA9IG5ldyBCb3VuZHMoKS5zZXRCb3VuZHNGcm9tRWxlbShzZWxmLmVsZW0pXG4gICAgICB2YXIgdGFyZ2V0Qm91bmRzID0gbmV3IEJvdW5kcygpLnNldEJvdW5kc0Zyb21FbGVtKHRhcmdldClcbiAgICAgIHZhciB2aXNpYmxlID0gdGFyZ2V0Qm91bmRzLndpdGhpbkJvdW5kcyhlbGVtQm91bmRzKVxuICAgICAgcmV0dXJuIHZpc2libGUgJiYgJCh0YXJnZXQpLmlzKCc6dmlzaWJsZScpXG4gICAgfVxuXG4gICAgLy8gQ2hlY2sgYWxsIHdhdGNoU2Nyb2xsTGlzdGVuZXJzIGFuZCBkZXRlcm1pbmVzIGlmIHRhcmdldHMgY2FuIGJlIGFjdGlvbmVkIHVwb25cbiAgICBzZWxmLmNoZWNrTGlzdGVuZXJzID0gZnVuY3Rpb24gKCkge1xuICAgICAgdmFyIHRhcmdldHNWaXNpYmxlID0gW11cblxuICAgICAgLy8gSXRlcmF0ZSBvdmVyIGFsbCBsaXN0ZW5lcnMgYW5kIGZpcmUgY2FsbGJhY2sgZGVwZW5kaW5nIG9uIHRhcmdldCdzIHN0YXRlIChlbnRlci9sZWF2ZS92aXNpYmxlL2hpZGRlbilcbiAgICAgIGZvciAoIHZhciB4IGluIHNlbGYubGlzdGVuZXJzICkge1xuICAgICAgICB2YXIgbGlzdGVuZXIgPSBzZWxmLmxpc3RlbmVyc1t4XVxuXG4gICAgICAgIC8vIEl0ZXJhdGUgdGhyb3VnaCBlYWNoIHRhcmdldFxuICAgICAgICBsaXN0ZW5lci4kdGFyZ2V0LmVhY2goIGZ1bmN0aW9uIChpLCB0YXJnZXQpIHtcbiAgICAgICAgICB2YXIgaXNWaXNpYmxlID0gc2VsZi5pc1Zpc2libGUodGFyZ2V0KVxuXG4gICAgICAgICAgLy8gU3RvcmUgdGhlIGlzVmlzaWJsZSB0byBhcHBseSBhcyB3YXNWaXNpYmxlIGFmdGVyIGFsbCBsaXN0ZW5lcnMgaGF2ZSBiZWVuIHByb2Nlc3NlZFxuICAgICAgICAgIHRhcmdldHNWaXNpYmxlLnB1c2goe1xuICAgICAgICAgICAgdGFyZ2V0OiB0YXJnZXQsXG4gICAgICAgICAgICB3YXNWaXNpYmxlOiBpc1Zpc2libGVcbiAgICAgICAgICB9KVxuXG4gICAgICAgICAgLy8gSXRlcmF0ZSB0aHJvdWdoIGVhY2ggYWN0aW9uXG4gICAgICAgICAgZm9yICggdmFyIHkgaW4gbGlzdGVuZXIuYWN0aW9uICkge1xuICAgICAgICAgICAgc2VsZi5jaGVja1RhcmdldEZvckFjdGlvbih0YXJnZXQsIGxpc3RlbmVyLmFjdGlvblt5XSwgbGlzdGVuZXIuY2FsbGJhY2spXG4gICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICAgIH1cblxuICAgICAgLy8gSXRlcmF0ZSBvdmVyIGFsbCB0YXJnZXRzIGFuZCBhcHBseSB0aGVpciBpc1Zpc2libGUgdmFsdWUgdG8gd2FzVmlzaWJsZVxuICAgICAgZm9yICggeCBpbiB0YXJnZXRzVmlzaWJsZSApIHtcbiAgICAgICAgdGFyZ2V0c1Zpc2libGVbeF0udGFyZ2V0Lndhc1Zpc2libGUgPSB0YXJnZXRzVmlzaWJsZVt4XS53YXNWaXNpYmxlXG4gICAgICB9XG4gICAgfVxuXG4gICAgLy8gQ2hlY2sgc2luZ2xlIHRhcmdldCBmb3Igc3RhdGVcbiAgICBzZWxmLmdldFRhcmdldFN0YXRlID0gZnVuY3Rpb24gKHRhcmdldCkge1xuICAgICAgdmFyICR0YXJnZXQgPSAkKHRhcmdldClcbiAgICAgIHRhcmdldCA9ICR0YXJnZXRbMF1cbiAgICAgIHZhciBzdGF0ZSA9IFtdXG5cbiAgICAgIC8vIFZpc2liaWxpdHlcbiAgICAgIHZhciB3YXNWaXNpYmxlID0gdGFyZ2V0Lndhc1Zpc2libGUgfHwgZmFsc2VcbiAgICAgIHZhciBpc1Zpc2libGUgPSBzZWxmLmlzVmlzaWJsZSh0YXJnZXQpXG5cbiAgICAgIC8vIEVudGVyXG4gICAgICBpZiAoICF3YXNWaXNpYmxlICYmIGlzVmlzaWJsZSApIHtcbiAgICAgICAgc3RhdGUucHVzaCgnZW50ZXInKVxuXG4gICAgICAvLyBWaXNpYmxlXG4gICAgICB9IGVsc2UgaWYgKCB3YXNWaXNpYmxlICYmIGlzVmlzaWJsZSApIHtcbiAgICAgICAgc3RhdGUucHVzaCgndmlzaWJsZScpXG5cbiAgICAgIC8vIExlYXZlXG4gICAgICB9IGVsc2UgaWYgKCB3YXNWaXNpYmxlICYmICFpc1Zpc2libGUgKSB7XG4gICAgICAgIHN0YXRlLnB1c2goJ2xlYXZlJylcblxuICAgICAgLy8gSGlkZGVuXG4gICAgICB9IGVsc2UgaWYgKCAhd2FzVmlzaWJsZSAmJiAhaXNWaXNpYmxlICkge1xuICAgICAgICBzdGF0ZS5wdXNoKCdoaWRkZW4nKVxuICAgICAgfVxuXG4gICAgICAvLyBAZGVidWcgY29uc29sZS5sb2coICdXYXRjaFNjcm9sbC5nZXRUYXJnZXRTdGF0ZScsIHdhc1Zpc2libGUsIGlzVmlzaWJsZSwgdGFyZ2V0IClcblxuICAgICAgcmV0dXJuIHN0YXRlLmpvaW4oJyAnKVxuICAgIH1cblxuICAgIC8vIEZpcmUgY2FsbGJhY2sgaWYgdGFyZ2V0IG1hdGNoZXMgYWN0aW9uXG4gICAgLy9cbiAgICAvLyBWYWxpZCBhY3Rpb25zOlxuICAgIC8vICAtLSBwb3NpdGlvblRvcD41MCAgICAgICA9PiB0YXJnZXQucG9zaXRpb25Ub3AgPiA1MFxuICAgIC8vICAtLSBzY3JvbGxUb3A+PDUwOjEwMCAgICA9PiB0YXJnZXQuc2Nyb2xsVG9wID4gNTAgJiYgdGFyZ2V0LnNjcm9sbFRvcCA8IDEwMFxuICAgIC8vICAtLSBvZmZzZXRUb3A8PT41MDoxMDAgICA9PiB0YXJnZXQub2Zmc2V0VG9wIDw9IDAgJiYgdGFyZ2V0Lm9mZnNldFRvcCA+PSAxMDBcbiAgICAvLyAgLS0gZW50ZXIgICAgICAgICAgICAgICAgPT4gdGFyZ2V0LmlzVmlzaWJsZSAmJiAhdGFyZ2V0Lndhc1Zpc2libGVcbiAgICAvL1xuICAgIC8vIFNlZSBzd2l0Y2ggY29udHJvbCBibG9ja3MgYmVsb3cgZm9yIG1vcmUgZXhwcmVzc2lvbnMgYW5kIHN0YXRlIGtleXdvcmRzXG4gICAgc2VsZi5jaGVja1RhcmdldEZvckFjdGlvbiA9IGZ1bmN0aW9uICh0YXJnZXQsIGFjdGlvbiwgY2FsbGJhY2spIHtcbiAgICAgIHZhciBkb0FjdGlvbiA9IGZhbHNlXG4gICAgICB2YXIgJHRhcmdldCA9ICQodGFyZ2V0KVxuICAgICAgdmFyIHRhcmdldCA9ICR0YXJnZXRbMF1cbiAgICAgIHZhciBzdGF0ZVxuXG4gICAgICAvLyBDdXN0b20gYWN0aW9uXG4gICAgICBpZiAodHlwZW9mIGFjdGlvbiA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAvLyBGaXJlIHRoZSBhY3Rpb24gdG8gc2VlIGlmIGl0IGFwcGxpZXNcbiAgICAgICAgZG9BY3Rpb24gPSBhY3Rpb24uYXBwbHkodGFyZ2V0LCBbe1xuICAgICAgICAgIFdhdGNoZXI6IHNlbGYsXG4gICAgICAgICAgdGFyZ2V0OiB0YXJnZXQsXG4gICAgICAgICAgY2FsbGJhY2s6IGNhbGxiYWNrXG4gICAgICAgIH1dKVxuXG4gICAgICAgIC8vIFN1Y2Nlc3NmdWwgYWN0aW9uIG1ldFxuICAgICAgICBpZiAoZG9BY3Rpb24pIHtcbiAgICAgICAgICAvLyBGaXJlIHRoZSBjYWxsYmFja1xuICAgICAgICAgIGlmICh0eXBlb2YgY2FsbGJhY2sgPT09ICdmdW5jdGlvbicpIGNhbGxiYWNrLmFwcGx5KHRhcmdldCwgW2RvQWN0aW9uXSlcblxuICAgICAgICAgIC8vIFRyaWdnZXIgYWN0aW9ucyBmb3IgYW55IG90aGVyIHRoaW5ncyB3YXRjaGluZ1xuICAgICAgICAgIC8vIElmIHlvdXIgY3VzdG9tIGFjdGlvbiByZXR1cm5zIGEgc3RyaW5nLCBpdCdsbCB0cmlnZ2VyICd3YXRjaHNjcm9sbC1hY3Rpb24te3JldHVybmVkIHN0cmluZ30nXG4gICAgICAgICAgaWYgKHR5cGVvZiBkb0FjdGlvbiA9PT0gJ3N0cmluZycpICQodGFyZ2V0KS50cmlnZ2VyKCd3YXRjaHNjcm9sbC1hY3Rpb24tJyArIGRvQWN0aW9uLCBbc2VsZl0pXG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gZG9BY3Rpb25cbiAgICAgIH1cblxuICAgICAgLy8gQWN0aW9uIGlzIGEgc3RyaW5nXG4gICAgICAvLyBFbnN1cmUgbG93ZXJjYXNlXG4gICAgICBhY3Rpb24gPSBhY3Rpb24udG9Mb3dlckNhc2UoKVxuXG4gICAgICAvLyBHZXQgdGFyZ2V0IHBvc2l0aW9uXG4gICAgICBpZiAoL14oKHBvc2l0aW9ufG9mZnNldHxzY3JvbGwpdG9wKS8udGVzdChhY3Rpb24pKSB7XG4gICAgICAgIC8vIEJyZWFrIGFjdGlvbiBpbnRvIGNvbXBvbmVudHMsIGUuZy4gc2Nyb2xsVG9wPjUwID0+IHNjcm9sbHRvcCwgPiwgNTBcbiAgICAgICAgdmFyIHByb3AgPSBhY3Rpb24ucmVwbGFjZSgvXigocG9zaXRpb258b2Zmc2V0fHNjcm9sbCl0b3ApLiokLywgJyQxJykudHJpbSgpXG4gICAgICAgIHZhciBleHAgPSBhY3Rpb24ucmVwbGFjZSgvXltcXHdcXHNdKyhbXFw8XFw+XFw9XSspLiovLCAnJDEnKS50cmltKClcbiAgICAgICAgdmFyIHZhbHVlID0gYWN0aW9uLnJlcGxhY2UoL15bXFx3XFxzXStbXFw8XFw+XFw9XSsoXFxzKltcXGRcXC1cXC5cXDpdKykkLywgJyQxJykudHJpbSgpXG4gICAgICAgIHZhciBjaGVja1ZhbHVlXG5cbiAgICAgICAgLy8gU3BsaXQgdmFsdWUgaWYgaXQgaXMgYSByYW5nZSAoaS5lLiBoYXMgYSBgOmAgc2VwYXJhdGluZyB0d28gbnVtYmVyczogYDEyMDo1MDBgKVxuICAgICAgICBpZiAoL1xcLT9cXGQrKFxcLltcXGQrXSk/OlxcLT9cXGQrKFxcLltcXGQrXSk/Ly50ZXN0KHZhbHVlKSkge1xuICAgICAgICAgIHZhbHVlID0gdmFsdWUuc3BsaXQoJzonKVxuICAgICAgICAgIHZhbHVlWzBdID0gcGFyc2VGbG9hdCh2YWx1ZVswXSlcbiAgICAgICAgICB2YWx1ZVsxXSA9IHBhcnNlRmxvYXQodmFsdWVbMV0pXG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgdmFsdWUgPSBwYXJzZUZsb2F0KHZhbHVlKVxuICAgICAgICB9XG5cbiAgICAgICAgLy8gR2V0IHRoZSB2YWx1ZSB0byBjaGVjayBiYXNlZCBvbiBwcm9wXG4gICAgICAgIHN3aXRjaCAocHJvcC50b0xvd2VyQ2FzZSgpKSB7XG4gICAgICAgICAgY2FzZSAncG9zaXRpb250b3AnOlxuICAgICAgICAgICAgY2hlY2tWYWx1ZSA9ICR0YXJnZXQucG9zaXRpb24oKS50b3BcbiAgICAgICAgICAgIGJyZWFrO1xuXG4gICAgICAgICAgY2FzZSAnb2Zmc2V0dG9wJzpcbiAgICAgICAgICAgIGNoZWNrVmFsdWUgPSAkdGFyZ2V0Lm9mZnNldCgpLnRvcFxuICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICBjYXNlICdzY3JvbGx0b3AnOlxuICAgICAgICAgICAgY2hlY2tWYWx1ZSA9ICR0YXJnZXQuc2Nyb2xsVG9wKClcbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCBhY3Rpb24sIHByb3AsIGV4cCwgdmFsdWUsIGNoZWNrVmFsdWUgKVxuXG4gICAgICAgIC8vIENvbXBhcmUgdmFsdWVzXG4gICAgICAgIHN3aXRjaCAoZXhwKSB7XG4gICAgICAgICAgLy8gZXFcbiAgICAgICAgICBjYXNlICc9JzpcbiAgICAgICAgICBjYXNlICc9PSc6XG4gICAgICAgICAgY2FzZSAnPT09JzpcbiAgICAgICAgICAgIGlmICggY2hlY2tWYWx1ZSA9PSB2YWx1ZSApIHtcbiAgICAgICAgICAgICAgZG9BY3Rpb24gPSB0cnVlXG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBicmVhaztcblxuICAgICAgICAgIC8vIG5lXG4gICAgICAgICAgY2FzZSAnIT0nOlxuICAgICAgICAgIGNhc2UgJyE9PSc6XG4gICAgICAgICAgICBpZiAoIGNoZWNrVmFsdWUgPT0gdmFsdWUgKSB7XG4gICAgICAgICAgICAgIGRvQWN0aW9uID0gdHJ1ZVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAvLyBndFxuICAgICAgICAgIGNhc2UgJz4nOlxuICAgICAgICAgICAgaWYgKCBjaGVja1ZhbHVlID4gdmFsdWUgKSB7XG4gICAgICAgICAgICAgIGRvQWN0aW9uID0gdHJ1ZVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAvLyBndGVcbiAgICAgICAgICBjYXNlICc+PSc6XG4gICAgICAgICAgICBpZiAoIGNoZWNrVmFsdWUgPj0gdmFsdWUgKSB7XG4gICAgICAgICAgICAgIGRvQWN0aW9uID0gdHJ1ZVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAvLyBsdFxuICAgICAgICAgIGNhc2UgJzwnOlxuICAgICAgICAgICAgaWYgKCBjaGVja1ZhbHVlIDwgdmFsdWUgKSB7XG4gICAgICAgICAgICAgIGRvQWN0aW9uID0gdHJ1ZVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAvLyBsdGVcbiAgICAgICAgICBjYXNlICc8PSc6XG4gICAgICAgICAgICBpZiAoIGNoZWNrVmFsdWUgPD0gdmFsdWUgKSB7XG4gICAgICAgICAgICAgIGRvQWN0aW9uID0gdHJ1ZVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAvLyBvdXRzaWRlIHJhbmdlXG4gICAgICAgICAgY2FzZSAnPD4nOlxuICAgICAgICAgICAgaWYgKCB2YWx1ZSBpbnN0YW5jZW9mIEFycmF5ICYmIChjaGVja1ZhbHVlIDwgdmFsdWVbMF0gJiYgY2hlY2tWYWx1ZSA+IHZhbHVlWzFdKSApIHtcbiAgICAgICAgICAgICAgZG9BY3Rpb24gPSB0cnVlXG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBicmVhaztcblxuICAgICAgICAgIC8vIG91dHNpZGUgcmFuZ2UgKGluY2x1ZGluZyBtaW46bWF4KVxuICAgICAgICAgIGNhc2UgJzw9Pic6XG4gICAgICAgICAgICBpZiAoIHZhbHVlIGluc3RhbmNlb2YgQXJyYXkgJiYgKGNoZWNrVmFsdWUgPD0gdmFsdWVbMF0gJiYgY2hlY2tWYWx1ZSA+PSB2YWx1ZVsxXSkgKSB7XG4gICAgICAgICAgICAgIGRvQWN0aW9uID0gdHJ1ZVxuICAgICAgICAgICAgfVxuICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAvLyBpbnNpZGUgcmFuZ2VcbiAgICAgICAgICBjYXNlICc+PCc6XG4gICAgICAgICAgICBpZiAoIHZhbHVlIGluc3RhbmNlb2YgQXJyYXkgJiYgKGNoZWNrVmFsdWUgPiB2YWx1ZVswXSAmJiBjaGVja1ZhbHVlIDwgdmFsdWVbMV0pICkge1xuICAgICAgICAgICAgICBkb0FjdGlvbiA9IHRydWVcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGJyZWFrO1xuXG4gICAgICAgICAgLy8gSW5zaWRlIHJhbmdlIChpbmNsdWRpbmcgbWluOm1heClcbiAgICAgICAgICBjYXNlICc+PTwnOlxuICAgICAgICAgICAgaWYgKCB2YWx1ZSBpbnN0YW5jZW9mIEFycmF5ICYmIChjaGVja1ZhbHVlID49IHZhbHVlWzBdICYmIGNoZWNrVmFsdWUgPD0gdmFsdWVbMV0pICkge1xuICAgICAgICAgICAgICBkb0FjdGlvbiA9IHRydWVcbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGJyZWFrO1xuICAgICAgICB9XG5cbiAgICAgIC8vIEtleXdvcmQgYWN0aW9ucyByZXByZXNlbnRpbmcgc3RhdGU6IGVudGVyLCBsZWF2ZSwgdmlzaWJsZSwgaGlkZGVuXG4gICAgICB9IGVsc2Uge1xuICAgICAgICBzdGF0ZSA9IHNlbGYuZ2V0VGFyZ2V0U3RhdGUodGFyZ2V0KVxuICAgICAgICBpZiAoc3RhdGUubWF0Y2goYWN0aW9uKSkge1xuICAgICAgICAgIGRvQWN0aW9uID0gdHJ1ZVxuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZyhzdGF0ZSwgZG9BY3Rpb24sIHRhcmdldCwgJHRhcmdldClcbiAgICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZyggJ1dhdGNoU2Nyb2xsLldhdGNoZXIuY2hlY2tUYXJnZXRGb3JBY3Rpb246JywgYWN0aW9uLCB0YXJnZXQgKVxuXG4gICAgICBpZiAoZG9BY3Rpb24pIHtcbiAgICAgICAgZG9BY3Rpb24gPSBhY3Rpb25cbiAgICAgICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCAnIC0tPiAnICsgZG9BY3Rpb24gKVxuICAgICAgICBpZiAodHlwZW9mIGNhbGxiYWNrID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgY2FsbGJhY2suYXBwbHkodGFyZ2V0KVxuICAgICAgICB9XG5cbiAgICAgICAgLy8gVHJpZ2dlciBhY3Rpb25zIGZvciBhbnkgb3RoZXIgdGhpbmdzIHdhdGNoaW5nXG4gICAgICAgIGlmICh0eXBlb2YgZG9BY3Rpb24gPT09ICdzdHJpbmcnKSAkKHRhcmdldCkudHJpZ2dlcignd2F0Y2hzY3JvbGwtYWN0aW9uLScgKyBkb0FjdGlvbiwgW3NlbGZdKVxuICAgICAgfVxuXG4gICAgICByZXR1cm4gZG9BY3Rpb25cbiAgICB9XG5cblxuICAgIC8qXG4gICAgICogRXZlbnRzXG4gICAgICovXG4gICAgc2VsZi4kZWxlbS5vbignc2Nyb2xsJywgZnVuY3Rpb24gKCBldmVudCApIHtcbiAgICAgIC8vIExldCB0aGUgYnJvd3NlciBkZXRlcm1pbmUgYmVzdCB0aW1lIHRvIGFuaW1hdGVcbiAgICAgIHJlcXVlc3RBbmltYXRpb25GcmFtZShzZWxmLmNoZWNrTGlzdGVuZXJzKVxuXG4gICAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ2V2ZW50LnNjcm9sbCcpXG4gICAgICBzZWxmLmNoZWNrTGlzdGVuZXJzKClcbiAgICB9KVxuXG4gICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKHNlbGYuZWxlbSlcbiAgICByZXR1cm4gc2VsZlxuICB9LFxuXG4gIC8qXG4gICAqIFdhdGNoU2Nyb2xsTGlzdGVuZXJcbiAgICogQGNsYXNzXG4gICAqL1xuICBMaXN0ZW5lcjogZnVuY3Rpb24gKHRhcmdldCwgYWN0aW9uLCBjYWxsYmFjaykge1xuICAgIHZhciBzZWxmID0gdGhpc1xuXG4gICAgLy8gTmVlZHMgYSB0YXJnZXQsIGFjdGlvbiBhbmQgY2FsbGJhY2tcbiAgICBpZiAodHlwZW9mIHRhcmdldCAhPT0gJ29iamVjdCcgJiYgdHlwZW9mIHRhcmdldCAhPT0gJ3N0cmluZycpIHJldHVybiBmYWxzZVxuICAgIGlmICh0eXBlb2YgYWN0aW9uICE9PSAnc3RyaW5nJyAmJiB0eXBlb2YgYWN0aW9uICE9PSAnZnVuY3Rpb24nKSByZXR1cm4gZmFsc2VcblxuICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnYWRkZWQgV2F0Y2hTY3JvbGxMaXN0ZW5lcicsIHRhcmdldCwgYWN0aW9uKVxuXG4gICAgLypcbiAgICAgKiBQcm9wZXJ0aWVzXG4gICAgICovXG4gICAgc2VsZi5XYXRjaFNjcm9sbFdhdGNoZXIgLy8gUGFyZW50IFdhdGNoU2Nyb2xsIFdhdGNoZXIsIGZvciByZWZlcmVuY2UgaWYgbmVlZGVkXG4gICAgc2VsZi4kdGFyZ2V0ID0gJCh0YXJnZXQpIC8vIFRoZSB0YXJnZXQocylcblxuICAgIC8vIENvbnZlcnQgYWN0aW9uIHRvIGFycmF5IG9mIGFjdGlvbihzKVxuICAgIGlmICh0eXBlb2YgYWN0aW9uID09PSAnc3RyaW5nJykge1xuICAgICAgc2VsZi5hY3Rpb24gPSAvXFxzLy50ZXN0KGFjdGlvbikgPyBhY3Rpb24uc3BsaXQoL1xccysvKSA6IFthY3Rpb25dXG4gICAgfSBlbHNlIHtcbiAgICAgIHNlbGYuYWN0aW9uID0gW2FjdGlvbl1cbiAgICB9XG5cbiAgICBzZWxmLmNhbGxiYWNrID0gY2FsbGJhY2tcbiAgICBzZWxmLmhhc0NhbGxiYWNrID0gKHR5cGVvZiBjYWxsYmFjayA9PT0gJ2Z1bmN0aW9uJylcbiAgICBpZiAoc2VsZi5oYXNDYWxsYmFjaykgc2VsZi5jYWxsYmFjayA9IGNhbGxiYWNrXG5cbiAgICAvKlxuICAgICAqIE1ldGhvZHNcbiAgICAgKi9cbiAgICAvLyBEbyBjYWxsYmFja1xuICAgIHNlbGYuZG9DYWxsYmFjayA9IGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICghc2VsZi5oYXNDYWxsYmFjaykgcmV0dXJuXG5cbiAgICAgIHNlbGYuJHRhcmdldC5lYWNoKCBmdW5jdGlvbiAoaSwgdGFyZ2V0KSB7XG4gICAgICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZyggJ1dhdGNoU2Nyb2xsTGlzdGVuZXInLCB0YXJnZXQsIHNlbGYuYWN0aW9uIClcbiAgICAgICAgc2VsZi5jYWxsYmFjay5hcHBseSh0YXJnZXQpXG4gICAgICB9KVxuICAgIH1cblxuICAgIHJldHVybiBzZWxmXG4gIH1cbn1cblxubW9kdWxlLmV4cG9ydHMgPSBXYXRjaFNjcm9sbFxuIiwiLypcbiAqIERpY3Rpb25hcnkgU2hvcnRjdXRcbiAqL1xuXG52YXIgJCA9ICh0eXBlb2Ygd2luZG93ICE9PSBcInVuZGVmaW5lZFwiID8gd2luZG93WydqUXVlcnknXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ2pRdWVyeSddIDogbnVsbClcbnZhciBEaWN0aW9uYXJ5ID0gcmVxdWlyZSgnRGljdGlvbmFyeScpXG52YXIgVU5JTEVORF9MQU5HID0gcmVxdWlyZSgnLi4vLi4vLi4vbGFuZy9VbmlsZW5kLmxhbmcuanNvbicpXG52YXIgX18gPSBuZXcgRGljdGlvbmFyeShVTklMRU5EX0xBTkcsICQoJ2h0bWwnKS5hdHRyKCdsYW5nJykgfHwgJ2ZyJylcblxubW9kdWxlLmV4cG9ydHMgPSBfX1xuIiwiLypcbiAqIFVuaWxlbmQgSlNcbiAqXG4gKiBAbGludGVyIFN0YW5kYXJkIEpTIChodHRwOi8vc3RhbmRhcmRqcy5jb20vKVxuICovXG5cbi8vIEBUT0RPIGVtcHJ1bnRlciBzaW0gZnVuY3Rpb25hbGl0eVxuLy8gQFRPRE8gQXV0b0NvbXBsZXRlIG5lZWRzIGhvb2tlZCB1cCB0byBBSkFYXG4vLyBAVE9ETyBTb3J0YWJsZSBtYXkgbmVlZCBBSkFYIGZ1bmN0aW9uYWxpdHlcbi8vIEBUT0RPIEZpbGVBdHRhY2ggbWF5IG5lZWQgQUpBWCBmdW5jdGlvbmFsaXR5XG5cbi8vIERlcGVuZGVuY2llc1xudmFyICQgPSAodHlwZW9mIHdpbmRvdyAhPT0gXCJ1bmRlZmluZWRcIiA/IHdpbmRvd1snalF1ZXJ5J10gOiB0eXBlb2YgZ2xvYmFsICE9PSBcInVuZGVmaW5lZFwiID8gZ2xvYmFsWydqUXVlcnknXSA6IG51bGwpIC8vIEdldHMgdGhlIGdsb2JhbCAoc2VlIHBhY2thZ2UuanNvbilcbnZhciB2aWRlb2pzID0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ3ZpZGVvanMnXSA6IHR5cGVvZiBnbG9iYWwgIT09IFwidW5kZWZpbmVkXCIgPyBnbG9iYWxbJ3ZpZGVvanMnXSA6IG51bGwpIC8vIEdldHMgdGhlIGdsb2JhbCAoc2VlIHBhY2thZ2UuanNvbilcbnZhciBzdmc0ZXZlcnlib2R5ID0gcmVxdWlyZSgnc3ZnNGV2ZXJ5Ym9keScpXG52YXIgU3dpcGVyID0gKHR5cGVvZiB3aW5kb3cgIT09IFwidW5kZWZpbmVkXCIgPyB3aW5kb3dbJ1N3aXBlciddIDogdHlwZW9mIGdsb2JhbCAhPT0gXCJ1bmRlZmluZWRcIiA/IGdsb2JhbFsnU3dpcGVyJ10gOiBudWxsKVxudmFyIEliYW4gPSByZXF1aXJlKCdpYmFuJylcblxuLy8gTGliXG52YXIgVXRpbGl0eSA9IHJlcXVpcmUoJ1V0aWxpdHknKVxudmFyIF9fID0gcmVxdWlyZSgnX18nKVxudmFyIFR3ZWVuID0gcmVxdWlyZSgnVHdlZW4nKVxudmFyIEVsZW1lbnRCb3VuZHMgPSByZXF1aXJlKCdFbGVtZW50Qm91bmRzJylcblxuLy8gQ29tcG9uZW50cyAmIGJlaGF2aW91cnNcbnZhciBBdXRvQ29tcGxldGUgPSByZXF1aXJlKCdBdXRvQ29tcGxldGUnKVxudmFyIFdhdGNoU2Nyb2xsID0gcmVxdWlyZSgnV2F0Y2hTY3JvbGwnKVxudmFyIFRleHRDb3VudCA9IHJlcXVpcmUoJ1RleHRDb3VudCcpXG52YXIgVGltZUNvdW50ID0gcmVxdWlyZSgnVGltZUNvdW50JylcbnZhciBTb3J0YWJsZSA9IHJlcXVpcmUoJ1NvcnRhYmxlJylcbnZhciBQYXNzd29yZENoZWNrID0gcmVxdWlyZSgnUGFzc3dvcmRDaGVjaycpXG52YXIgRmlsZUF0dGFjaCA9IHJlcXVpcmUoJ0ZpbGVBdHRhY2gnKVxudmFyIEZvcm1WYWxpZGF0aW9uID0gcmVxdWlyZSgnRm9ybVZhbGlkYXRpb24nKVxudmFyIERhc2hib2FyZFBhbmVsID0gcmVxdWlyZSgnRGFzaGJvYXJkUGFuZWwnKVxuLy8gdmFyIFN0aWNreSA9IHJlcXVpcmUoJ1N0aWNreScpIC8vIEBub3RlIHVuZmluaXNoZWRcblxuLy9cbiQoZG9jdW1lbnQpLnJlYWR5KGZ1bmN0aW9uICgkKSB7XG4gIC8vIE1haW4gdmFycy9lbGVtZW50c1xuICB2YXIgJGRvYyA9ICQoZG9jdW1lbnQpXG4gIHZhciAkaHRtbCA9ICQoJ2h0bWwnKVxuICB2YXIgJHdpbiA9ICQod2luZG93KVxuICB2YXIgJHNpdGVIZWFkZXIgPSAkKCcuc2l0ZS1oZWFkZXInKVxuICB2YXIgJHNpdGVGb290ZXIgPSAkKCcuc2l0ZS1mb290ZXInKVxuXG4gIC8vIFJlbW92ZSBIVE1MXG4gICRodG1sLnJlbW92ZUNsYXNzKCduby1qcycpXG5cbiAgLy8gVFdCUyBzZXR1cFxuICAvLyAkLnN1cHBvcnQudHJhbnNpdGlvbiA9IGZhbHNlXG4gIC8vIEJvb3RzdHJhcCBUb29sdGlwc1xuICAgJCgnW2RhdGEtdG9nZ2xlPVwidG9vbHRpcFwiXScpLnRvb2x0aXAoKVxuXG4gIC8vIEJyZWFrcG9pbnRzXG4gIC8vIC0tIERldmljZXNcbiAgdmFyIGJyZWFrcG9pbnRzID0ge1xuICAgICdtb2JpbGUtcCc6IFswLCA1OTldLFxuICAgICdtb2JpbGUtbCc6IFs2MDAsIDc5OV0sXG4gICAgJ3RhYmxldC1wJzogWzgwMCwgMTAyM10sXG4gICAgJ3RhYmxldC1sJzogWzEwMjQsIDEyOTldLFxuICAgICdsYXB0b3AnOiAgIFsxMzAwLCAxNTk5XSxcbiAgICAnZGVza3RvcCc6ICBbMTYwMCwgOTk5OTldXG4gIH1cblxuICAvLyAtLSBEZXZpY2UgZ3JvdXBzXG4gIGJyZWFrcG9pbnRzLm1vYmlsZSA9IFswLCBicmVha3BvaW50c1snbW9iaWxlLWwnXVsxXV1cbiAgYnJlYWtwb2ludHMudGFibGV0ID0gW2JyZWFrcG9pbnRzWyd0YWJsZXQtcCddWzBdLCBicmVha3BvaW50c1sndGFibGV0LWwnXVsxXV1cbiAgYnJlYWtwb2ludHMuY29tcHV0ZXIgPSBbYnJlYWtwb2ludHNbJ2xhcHRvcCddWzBdLCBicmVha3BvaW50c1snZGVza3RvcCddWzFdXVxuXG4gIC8vIC0tIEdyaWRzXG4gIGJyZWFrcG9pbnRzLnhzID0gYnJlYWtwb2ludHNbJ21vYmlsZS1wJ11cbiAgYnJlYWtwb2ludHMuc20gPSBbYnJlYWtwb2ludHNbJ21vYmlsZS1sJ11bMF0sIGJyZWFrcG9pbnRzWyd0YWJsZXQtcCddWzFdXVxuICBicmVha3BvaW50cy5tZCA9IFticmVha3BvaW50c1sndGFibGV0LWwnXVswXSwgYnJlYWtwb2ludHNbJ2xhcHRvcCddWzFdXVxuICBicmVha3BvaW50cy5sZyA9IGJyZWFrcG9pbnRzWydkZXNrdG9wJ11cblxuICAvLyBUcmFjayB0aGUgY3VycmVudCBicmVha3BvaW50cyAoYWxzbyB1cGRhdGVkIGluIHVwZGF0ZVdpbmRvdygpKVxuICB2YXIgY3VycmVudEJyZWFrcG9pbnQgPSBnZXRBY3RpdmVCcmVha3BvaW50cygpXG5cbiAgLy8gVmlkZW9KU1xuICAvLyBSdW5uaW5nIGEgbW9kaWZpZWQgdmVyc2lvbiB0byBjdXN0b21pc2UgdGhlIHBsYWNlbWVudCBvZiBpdGVtcyBpbiB0aGUgY29udHJvbCBiYXJcbiAgdmlkZW9qcy5vcHRpb25zLmZsYXNoLnN3ZiA9IG51bGwgLy8gQFRPRE8gbmVlZHMgY29ycmVjdCBsaW5rICcvanMvdmVuZG9yL3ZpZGVvanMvdmlkZW8tanMuc3dmJ1xuXG4gIC8vIHNpdGVTZWFyY2ggYXV0b2NvbXBsZXRlXG4gIHZhciBzaXRlU2VhcmNoQXV0b0NvbXBsZXRlID0gbmV3IEF1dG9Db21wbGV0ZSgnLnNpdGUtaGVhZGVyIC5zaXRlLXNlYXJjaC1pbnB1dCcsIHtcbiAgICAvLyBAVE9ETyBldmVudHVhbGx5IHdoZW4gQUpBWCBpcyBjb25uZWN0ZWQsIHRoZSBVUkwgd2lsbCBnbyBoZXJlXG4gICAgLy8gYWpheFVybDogJycsXG4gICAgdGFyZ2V0OiAnLnNpdGUtaGVhZGVyIC5zaXRlLXNlYXJjaCAuYXV0b2NvbXBsZXRlJ1xuICB9KVxuXG4gIC8vIFNpdGUgU2VhcmNoXG4gIHZhciBzaXRlU2VhcmNoVGltZW91dCA9IDBcblxuICAvLyAtLSBFdmVudHNcbiAgJGRvY1xuICAgIC8vIEFjdGl2YXRlL2ZvY3VzIC5zaXRlLXNlYXJjaC1pbnB1dFxuICAgIC5vbihVdGlsaXR5LmNsaWNrRXZlbnQgKyAnIGFjdGl2ZSBmb2N1cyBrZXlkb3duJywgJy5zaXRlLXNlYXJjaC1pbnB1dCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgb3BlblNpdGVTZWFyY2goKVxuICAgIH0pXG4gICAgLy8gSG92ZXIgb3ZlciAuc2l0ZS1zZWFyY2ggLmF1dG9jb21wbGV0ZVxuICAgIC5vbignbW91c2VlbnRlciBtb3VzZW92ZXInLCAnLnNpdGUtc2VhcmNoIC5hdXRvY29tcGxldGUnLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgIG9wZW5TaXRlU2VhcmNoKClcbiAgICB9KVxuXG4gICAgLy8gRGlzbWlzcyBzaXRlIHNlYXJjaCBhZnRlciBibHVyXG4gICAgLm9uKCdrZXlkb3duJywgJy5zaXRlLXNlYXJjaC1pbnB1dCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdrZXl1cCcsICcuc2l0ZS1zZWFyY2gtaW5wdXQnKVxuICAgICAgLy8gRGlzbWlzc1xuICAgICAgaWYgKGV2ZW50LndoaWNoID09PSAyNykge1xuICAgICAgICBjbG9zZVNpdGVTZWFyY2goMClcbiAgICAgICAgJCh0aGlzKS5ibHVyKClcbiAgICAgIH1cbiAgICB9KVxuICAgIC5vbignYmx1cicsICcuc2l0ZS1zZWFyY2gtaW5wdXQsIC5zaXRlLXNlYXJjaCAuYXV0b2NvbXBsZXRlLXJlc3VsdHMgYScsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdibHVyJywgJy5zaXRlLXNlYXJjaC1pbnB1dCcpXG4gICAgICBjbG9zZVNpdGVTZWFyY2goMjAwKVxuICAgIH0pXG4gICAgLy8gQGRlYnVnXG4gICAgLy8gLm9uKCdtb3VzZWxlYXZlJywgJy5zaXRlLWhlYWRlciAuc2l0ZS1zZWFyY2gnLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAvLyAgIGNvbnNvbGUubG9nKCdtb3VzZWxlYXZlJywgJy5zaXRlLXNlYXJjaCcpXG5cbiAgICAvLyAgIC8vIERvbid0IGRpc21pc3NcbiAgICAvLyAgIGlmICgkKCcuc2l0ZS1oZWFkZXIgLnNpdGUtc2VhcmNoLWlucHV0JykuaXMoJzpmb2N1cywgOmFjdGl2ZScpKSB7XG4gICAgLy8gICAgIHJldHVyblxuICAgIC8vICAgfVxuXG4gICAgLy8gICBjbG9zZVNpdGVTZWFyY2goKVxuICAgIC8vIH0pXG5cbiAgICAvLyBTdG9wIHNpdGUgc2VhcmNoIGRpc21pc3Npbmcgd2hlbiBob3ZlciBpbiBhdXRvY29tcGxldGVcbiAgICAub24oJ21vdXNlZW50ZXIgbW91c2VvdmVyJywgJy5zaXRlLXNlYXJjaCAuYXV0b2NvbXBsZXRlJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ21vdXNlZW50ZXIgbW91c2VvdmVyJywgJy5zaXRlLWhlYWRlciAuc2l0ZS1zZWFyY2ggLmF1dG9jb21wbGV0ZSBhJylcbiAgICAgIGNhbmNlbENsb3NlU2l0ZVNlYXJjaCgpXG4gICAgfSlcbiAgICAvLyBTdG9wIHNpdGUgc2VhcmNoIGRpc21pc3Npbmcgd2hlbiBmb2N1cy9hY3RpdmUgbGlua3MgaW4gYXV0b2NvbXBsZXRlXG4gICAgLm9uKCdrZXlkb3duIGZvY3VzIGFjdGl2ZScsICcuc2l0ZS1zZWFyY2ggLmF1dG9jb21wbGV0ZSBhJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ2tleWRvd24gZm9jdXMgYWN0aXZlJywgJy5zaXRlLWhlYWRlciAuc2l0ZS1zZWFyY2ggLmF1dG9jb21wbGV0ZSBhJylcbiAgICAgIGNhbmNlbENsb3NlU2l0ZVNlYXJjaCgpXG4gICAgfSlcblxuICAvLyAtLSBNZXRob2RzXG4gIGZ1bmN0aW9uIG9wZW5TaXRlU2VhcmNoICgpIHtcbiAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ29wZW5TaXRlU2VhcmNoJylcbiAgICBjYW5jZWxDbG9zZVNpdGVTZWFyY2goKVxuICAgICRodG1sLmFkZENsYXNzKCd1aS1zaXRlLXNlYXJjaC1vcGVuJylcbiAgfVxuXG4gIGZ1bmN0aW9uIGNsb3NlU2l0ZVNlYXJjaCAodGltZW91dCkge1xuICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnY2xvc2VTaXRlU2VhcmNoJywgdGltZW91dClcblxuICAgIC8vIERlZmF1bHRzIHRvIHRpbWUgb3V0IGFmdGVyIC41c1xuICAgIGlmICh0eXBlb2YgdGltZW91dCA9PT0gJ3VuZGVmaW5lZCcpIHRpbWVvdXQgPSA1MDBcblxuICAgIHNpdGVTZWFyY2hUaW1lb3V0ID0gc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICAkaHRtbC5yZW1vdmVDbGFzcygndWktc2l0ZS1zZWFyY2gtb3BlbicpXG5cbiAgICAgIC8vIEhpZGUgdGhlIGF1dG9jb21wbGV0ZVxuICAgICAgc2l0ZVNlYXJjaEF1dG9Db21wbGV0ZS5oaWRlKClcbiAgICB9LCB0aW1lb3V0KVxuICB9XG5cbiAgZnVuY3Rpb24gY2FuY2VsQ2xvc2VTaXRlU2VhcmNoICgpIHtcbiAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ2NhbmNlbENsb3NlU2l0ZVNlYXJjaCcpXG4gICAgY2xlYXJUaW1lb3V0KHNpdGVTZWFyY2hUaW1lb3V0KVxuICB9XG5cbiAgLypcbiAgICogU2l0ZSBNb2JpbGUgTWVudVxuICAgKi9cbiAgLy8gU2hvdyB0aGUgc2l0ZSBtb2JpbGUgbWVudVxuICAkZG9jLm9uKFV0aWxpdHkuY2xpY2tFdmVudCwgJy5zaXRlLW1vYmlsZS1tZW51LW9wZW4nLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpXG4gICAgb3BlblNpdGVNb2JpbGVNZW51KClcbiAgfSlcblxuICAvLyBDbG9zZSB0aGUgc2l0ZSBtb2JpbGUgbWVudVxuICAkZG9jLm9uKFV0aWxpdHkuY2xpY2tFdmVudCwgJy5zaXRlLW1vYmlsZS1tZW51LWNsb3NlJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgIGNsb3NlU2l0ZU1vYmlsZU1lbnUoKVxuICB9KVxuXG4gIC8vIEF0IGVuZCBvZiBvcGVuaW5nIGFuaW1hdGlvblxuICAkZG9jLm9uKFV0aWxpdHkuYW5pbWF0aW9uRW5kRXZlbnQsICcudWktc2l0ZS1tb2JpbGUtbWVudS1vcGVuaW5nJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgc2hvd1NpdGVNb2JpbGVNZW51KClcbiAgfSlcblxuICAvLyBBdCBlbmQgb2YgY2xvc2luZyBhbmltYXRpb25cbiAgJGRvYy5vbihVdGlsaXR5LmFuaW1hdGlvbkVuZEV2ZW50LCAnLnVpLXNpdGUtbW9iaWxlLW1lbnUtY2xvc2luZycsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGhpZGVTaXRlTW9iaWxlTWVudSgpXG4gIH0pXG5cbiAgZnVuY3Rpb24gb3BlblNpdGVNb2JpbGVNZW51ICgpIHtcbiAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ29wZW5TaXRlTW9iaWxlTWVudScpXG4gICAgaWYgKGlzSUUoOSkgfHwgaXNJRSgnPDknKSkgcmV0dXJuIHNob3dTaXRlTW9iaWxlTWVudSgpXG4gICAgaWYgKCEkaHRtbC5pcygnLnVpLXNpdGUtbW9iaWxlLW1lbnUtb3BlbiwgLnVpLXNpdGUtbW9iaWxlLW1lbnUtb3BlbmluZycpKSB7XG4gICAgICAkaHRtbC5yZW1vdmVDbGFzcygndWktc2l0ZS1tb2JpbGUtbWVudS1jbG9zaW5nJykuYWRkQ2xhc3MoJ3VpLXNpdGUtbW9iaWxlLW1lbnUtb3BlbmluZycpXG4gICAgfVxuICB9XG5cbiAgZnVuY3Rpb24gY2xvc2VTaXRlTW9iaWxlTWVudSAoKSB7XG4gICAgaWYgKGlzSUUoOSkgfHwgaXNJRSgnPDknKSkgcmV0dXJuIGhpZGVTaXRlTW9iaWxlTWVudSgpXG4gICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdjbG9zZVNpdGVNb2JpbGVNZW51JylcbiAgICAkaHRtbC5yZW1vdmVDbGFzcygndWktc2l0ZS1tb2JpbGUtbWVudS1vcGVuaW5nIHVpLXNpdGUtbW9iaWxlLW1lbnUtb3BlbicpLmFkZENsYXNzKCd1aS1zaXRlLW1vYmlsZS1tZW51LWNsb3NpbmcnKVxuICB9XG5cbiAgZnVuY3Rpb24gc2hvd1NpdGVNb2JpbGVNZW51ICgpIHtcbiAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ3Nob3dTaXRlTW9iaWxlTWVudScpXG4gICAgJGh0bWwuYWRkQ2xhc3MoJ3VpLXNpdGUtbW9iaWxlLW1lbnUtb3BlbicpLnJlbW92ZUNsYXNzKCd1aS1zaXRlLW1vYmlsZS1tZW51LW9wZW5pbmcgdWktc2l0ZS1tb2JpbGUtbWVudS1jbG9zaW5nJylcblxuICAgIC8vIEFSSUEgc3R1ZmZcbiAgICAkKCcuc2l0ZS1tb2JpbGUtbWVudScpLnJlbW92ZUF0dHIoJ2FyaWEtaGlkZGVuJylcbiAgICAkKCcuc2l0ZS1tb2JpbGUtbWVudSBbdGFiaW5kZXhdJykuYXR0cigndGFiaW5kZXgnLCAxKVxuICB9XG5cbiAgZnVuY3Rpb24gaGlkZVNpdGVNb2JpbGVNZW51ICgpIHtcbiAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ2hpZGVTaXRlTW9iaWxlTWVudScpXG4gICAgJGh0bWwucmVtb3ZlQ2xhc3MoJ3VpLXNpdGUtbW9iaWxlLW1lbnUtb3BlbmluZyB1aS1zaXRlLW1vYmlsZS1tZW51LWNsb3NpbmcgdWktc2l0ZS1tb2JpbGUtbWVudS1vcGVuJylcblxuICAgIC8vIEFSSUEgc3R1ZmZcbiAgICAkKCcuc2l0ZS1tb2JpbGUtbWVudScpLmF0dHIoJ2FyaWEtaGlkZGVuJywgJ3RydWUnKVxuICAgICQoJy5zaXRlLW1vYmlsZS1tZW51IFt0YWJpbmRleF0nKS5hdHRyKCd0YWJpbmRleCcsIC0xKVxuICB9XG5cbiAgLypcbiAgICogU2l0ZSBNb2JpbGUgU2VhcmNoXG4gICAqL1xuXG4gIC8vIENsaWNrIGJ1dHRvbiBzZWFyY2hcbiAgJGRvYy5vbihVdGlsaXR5LmNsaWNrRXZlbnQsICcuc2l0ZS1tb2JpbGUtc2VhcmNoLXRvZ2dsZScsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICBpZiAoISRodG1sLmlzKCcudWktc2l0ZS1tb2JpbGUtc2VhcmNoLW9wZW4nKSkge1xuICAgICAgb3BlblNpdGVNb2JpbGVTZWFyY2goKVxuICAgIH0gZWxzZSB7XG4gICAgICBjbG9zZVNpdGVNb2JpbGVTZWFyY2goKVxuICAgIH1cbiAgfSlcblxuICAvLyBGb2N1cy9hY3RpdmF0ZSBpbnB1dFxuICAkZG9jLm9uKCdmb2N1cyBhY3RpdmUnLCAnLnNpdGUtbW9iaWxlLXNlYXJjaC1pbnB1dCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnZm9jdXMgYWN0aXZlIC5zaXRlLW1vYmlsZS1zZWFyY2gtaW5wdXQnKVxuICAgIG9wZW5TaXRlTW9iaWxlU2VhcmNoKClcbiAgfSlcblxuICAvLyBCbHVyIGlucHV0XG4gIC8vICRkb2Mub24oJ2JsdXInLCAnLnNpdGUtbW9iaWxlLXNlYXJjaC1pbnB1dCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAvLyAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnYmx1ciBzaXRlLW1vYmlsZS1zZWFyY2gtaW5wdXQnKVxuICAvLyAgIGNsb3NlU2l0ZU1vYmlsZVNlYXJjaCgpXG4gIC8vIH0pXG5cbiAgZnVuY3Rpb24gb3BlblNpdGVNb2JpbGVTZWFyY2ggKCkge1xuICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnb3BlblNpdGVNb2JpbGVTZWFyY2gnKVxuICAgIG9wZW5TaXRlTW9iaWxlTWVudSgpXG4gICAgJGh0bWwuYWRkQ2xhc3MoJ3VpLXNpdGUtbW9iaWxlLXNlYXJjaC1vcGVuJylcbiAgfVxuXG4gIGZ1bmN0aW9uIGNsb3NlU2l0ZU1vYmlsZVNlYXJjaCAoKSB7XG4gICAgJGh0bWwucmVtb3ZlQ2xhc3MoJ3VpLXNpdGUtbW9iaWxlLXNlYXJjaC1vcGVuJylcbiAgfVxuXG4gIC8qXG4gICAqIE9wZW4gc2VhcmNoIChhdXRvLWRldGVjdHMgd2hpY2gpXG4gICAqL1xuICBmdW5jdGlvbiBvcGVuU2VhcmNoKCkge1xuICAgIC8vIE1vYmlsZSBzaXRlIHNlYXJjaFxuICAgIGlmICgveHN8c20vLnRlc3QoY3VycmVudEJyZWFrcG9pbnQpKSB7XG4gICAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ29wZW5TaXRlTW9iaWxlU2VhcmNoJylcbiAgICAgIG9wZW5TaXRlTW9iaWxlU2VhcmNoKClcbiAgICAgICQoJy5zaXRlLW1vYmlsZS1zZWFyY2gtaW5wdXQnKS5mb2N1cygpXG5cbiAgICAvLyBSZWd1bGFyIHNpdGUgc2VhcmNoXG4gICAgfSBlbHNlIHtcbiAgICAgICQoJy5zaXRlLXNlYXJjaC1pbnB1dCcpLmZvY3VzKClcbiAgICB9XG4gIH1cblxuICAvLyBPcGVuIHRoZSBzaXRlLXNlYXJjaCBmcm9tIGEgZGlmZmVyZW50IGJ1dHRvblxuICAkZG9jLm9uKCdjbGljaycsICcudWktb3Blbi1zaXRlLXNlYXJjaCcsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICBvcGVuU2VhcmNoKClcbiAgfSlcblxuICAvKlxuICAgKiBGYW5jeUJveFxuICAgKi9cbiAgJCgnLmZhbmN5Ym94JykuZmFuY3lib3goKVxuICAkKCcuZmFuY3lib3gtbWVkaWEnKS5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgdmFyICRlbGVtID0gJChlbGVtKVxuICAgIGlmICgkZWxlbS5pcygnLmZhbmN5Ym94LWVtYmVkLXZpZGVvanMnKSkge1xuICAgICAgJGVsZW0uZmFuY3lib3goe1xuICAgICAgICBwYWRkaW5nOiAwLFxuICAgICAgICBtYXJnaW46IDAsXG4gICAgICAgIGF1dG9TaXplOiB0cnVlLFxuICAgICAgICBhdXRvQ2VudGVyOiB0cnVlLFxuICAgICAgICBjb250ZW50OiAnPGRpdiBjbGFzcz1cImZhbmN5Ym94LXZpZGVvXCI+PHZpZGVvIGlkPVwiZmFuY3lib3gtdmlkZW9qc1wiIGNsYXNzPVwidmlkZW8tanNcIiBhdXRvcGxheSBjb250cm9scyBwcmVsb2FkPVwiYXV0b1wiIGRhdGEtc2V0dXA9XFwneyBcInRlY2hPcmRlclwiOiBbXCJ5b3V0dWJlXCJdLCBcInNvdXJjZXNcIjogW3sgXCJ0eXBlXCI6IFwidmlkZW8veW91dHViZVwiLCBcInNyY1wiOiBcIicgKyAkZWxlbS5hdHRyKCdocmVmJykgKyAnXCIgfV0sIFwiaW5hY3Rpdml0eVRpbWVvdXRcIjogMCB9XFwnPjwvdmlkZW8+PC9kaXY+JyxcbiAgICAgICAgYmVmb3JlU2hvdzogZnVuY3Rpb24gKCkge1xuICAgICAgICAgICQuZmFuY3lib3guc2hvd0xvYWRpbmcoKVxuICAgICAgICAgICQoJy5mYW5jeWJveC1vdmVybGF5JykuYWRkQ2xhc3MoJ2ZhbmN5Ym94LWxvYWRpbmcnKVxuICAgICAgICB9LFxuICAgICAgICAvLyBBc3NpZ24gdmlkZW8gcGxheWVyIGZ1bmN0aW9uYWxpdHlcbiAgICAgICAgYWZ0ZXJTaG93OiBmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgLy8gVmlkZW8gbm90IGFzc2lnbmVkIHlldFxuICAgICAgICAgIGlmICghdmlkZW9qcy5nZXRQbGF5ZXJzKCkuaGFzT3duUHJvcGVydHkoJ2ZhbmN5Ym94LXZpZGVvJykpIHtcbiAgICAgICAgICAgIHZpZGVvanMoJyNmYW5jeWJveC12aWRlb2pzJywge30sIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgdmFyIHZpZGVvUGxheWVyID0gdGhpc1xuXG4gICAgICAgICAgICAgIC8vIFNldCB2aWRlbyB3aWR0aFxuICAgICAgICAgICAgICB2YXIgdmlkZW9XaWR0aCA9IHdpbmRvdy5pbm5lcldpZHRoICogMC43XG4gICAgICAgICAgICAgIGlmICh2aWRlb1dpZHRoIDwgMjgwKSB2aWRlb1dpZHRoID0gMjgwXG4gICAgICAgICAgICAgIGlmICh2aWRlb1dpZHRoID4gMTk4MCkgdmlkZW9XaWR0aCA9IDE5ODBcbiAgICAgICAgICAgICAgdmlkZW9QbGF5ZXIud2lkdGgodmlkZW9XaWR0aClcblxuICAgICAgICAgICAgICAvLyBVcGRhdGUgdGhlIGZhbmN5Ym94IHdpZHRoXG4gICAgICAgICAgICAgICQuZmFuY3lib3gudXBkYXRlKClcbiAgICAgICAgICAgICAgJC5mYW5jeWJveC5oaWRlTG9hZGluZygpXG4gICAgICAgICAgICAgIHNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgICQoJy5mYW5jeWJveC1vdmVybGF5JykucmVtb3ZlQ2xhc3MoJ2ZhbmN5Ym94LWxvYWRpbmcnKVxuICAgICAgICAgICAgICB9LCAyMDApXG4gICAgICAgICAgICB9KVxuICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAkLmZhbmN5Ym94LnVwZGF0ZSgpXG4gICAgICAgICAgICAkLmZhbmN5Ym94LmhpZGVMb2FkaW5nKClcbiAgICAgICAgICAgIHNldFRpbWVvdXQoZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAkKCcuZmFuY3lib3gtb3ZlcmxheScpLnJlbW92ZUNsYXNzKCdmYW5jeWJveC1sb2FkaW5nJylcbiAgICAgICAgICAgIH0sIDIwMClcbiAgICAgICAgICB9XG4gICAgICAgIH0sXG4gICAgICAgIC8vIFJlbW92ZSB2aWRlbyBwbGF5ZXIgb24gY2xvc2VcbiAgICAgICAgYWZ0ZXJDbG9zZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAgIHZpZGVvanMuZ2V0UGxheWVycygpWydmYW5jeWJveC12aWRlb2pzJ10uZGlzcG9zZSgpXG4gICAgICAgIH1cbiAgICAgIH0pXG4gICAgfSBlbHNlIHtcbiAgICAgICRlbGVtLmZhbmN5Ym94KHtcbiAgICAgICAgaGVscGVyczoge1xuICAgICAgICAgICdtZWRpYSc6IHt9XG4gICAgICAgIH1cbiAgICAgIH0pXG4gICAgfVxuICB9KVxuXG4gIC8qXG4gICAqIFN3aXBlclxuICAgKi9cbiAgJCgnLnN3aXBlci1jb250YWluZXInKS5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgdmFyICRlbGVtID0gJChlbGVtKVxuICAgIHZhciBzd2lwZXJPcHRpb25zID0ge1xuICAgICAgZGlyZWN0aW9uOiAkZWxlbS5hdHRyKCdkYXRhLXN3aXBlci1kaXJlY3Rpb24nKSB8fCAnaG9yaXpvbnRhbCcsXG4gICAgICBsb29wOiAkZWxlbS5hdHRyKCdkYXRhLXN3aXBlci1sb29wJykgPT09ICd0cnVlJyxcbiAgICAgIGVmZmVjdDogJGVsZW0uYXR0cignZGF0YS1zd2lwZXItZWZmZWN0JykgfHwgJ2ZhZGUnLFxuICAgICAgc3BlZWQ6IHBhcnNlSW50KCRlbGVtLmF0dHIoJ2RhdGEtc3dpcGVyLXNwZWVkJyksIDEwKSB8fCAyNTAsXG4gICAgICBhdXRvcGxheTogcGFyc2VJbnQoJGVsZW0uYXR0cignZGF0YS1zd2lwZXItYXV0b3BsYXknKSwgMTApIHx8IDUwMDAsXG4gICAgICAvLyBBUklBIGtleWJvYXJkIGZ1bmN0aW9uYWxpdHlcbiAgICAgIGExMXk6ICRlbGVtLmF0dHIoJ2RhdGEtc3dpcGVyLWFyaWEnKSA9PT0gJ3RydWUnXG4gICAgfVxuXG4gICAgLy8gRmFkZSAvIENyb3NzZmFkZVxuICAgIGlmIChzd2lwZXJPcHRpb25zLmVmZmVjdCA9PT0gJ2ZhZGUnKSB7XG4gICAgICBzd2lwZXJPcHRpb25zLmZhZGUgPSB7XG4gICAgICAgIGNyb3NzRmFkZTogJGVsZW0uYXR0cignZGF0YS1zd2lwZXItY3Jvc3NmYWRlJykgPT09ICd0cnVlJ1xuICAgICAgfVxuICAgIH1cblxuICAgIC8vIER5bmFtaWNhbGx5IHRlc3QgaWYgaGFzIHBhZ2luYXRpb25cbiAgICBpZiAoJGVsZW0uZmluZCgnLnN3aXBlci1jdXN0b20tcGFnaW5hdGlvbicpLmxlbmd0aCA+IDAgJiYgJGVsZW0uZmluZCgnLnN3aXBlci1jdXN0b20tcGFnaW5hdGlvbiA+IConKS5sZW5ndGggPiAwKSB7XG4gICAgICBzd2lwZXJPcHRpb25zLnBhZ2luYXRpb25UeXBlID0gJ2N1c3RvbSdcbiAgICB9XG5cbiAgICB2YXIgZWxlbVN3aXBlciA9IG5ldyBTd2lwZXIoZWxlbSwgc3dpcGVyT3B0aW9ucylcbiAgICAvLyBjb25zb2xlLmxvZyhlbGVtU3dpcGVyKVxuXG4gICAgLy8gQWRkIGV2ZW50IHRvIGhvb2sgdXAgY3VzdG9tIHBhZ2luYXRpb24gdG8gYXBwcm9wcmlhdGUgc2xpZGVcbiAgICBpZiAoc3dpcGVyT3B0aW9ucy5wYWdpbmF0aW9uVHlwZSA9PT0gJ2N1c3RvbScpIHtcbiAgICAgIC8vIEhvb2sgaW50byBzbGlkZXJNb3ZlIGV2ZW50IHRvIHVwZGF0ZSBjdXN0b20gcGFnaW5hdGlvblxuICAgICAgZWxlbVN3aXBlci5vbignc2xpZGVDaGFuZ2VTdGFydCcsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgLy8gVW5hY3RpdmUgYW55IGFjdGl2ZSBwYWdpbmF0aW9uIGl0ZW1zXG4gICAgICAgICRlbGVtLmZpbmQoJy5zd2lwZXItY3VzdG9tLXBhZ2luYXRpb24gbGkuYWN0aXZlJykucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpXG5cbiAgICAgICAgLy8gQWN0aXZhdGUgdGhlIGN1cnJlbnQgcGFnaW5hdGlvbiBpdGVtXG4gICAgICAgICRlbGVtLmZpbmQoJy5zd2lwZXItY3VzdG9tLXBhZ2luYXRpb24gbGk6ZXEoJyArIGVsZW1Td2lwZXIuYWN0aXZlSW5kZXggKyAnKScpLmFkZENsYXNzKCdhY3RpdmUnKVxuXG4gICAgICAgIC8vIGNvbnNvbGUubG9nKCdzbGlkZXJNb3ZlJywgZWxlbVN3aXBlci5hY3RpdmVJbmRleClcbiAgICAgIH0pXG5cbiAgICAgIC8vIENvbm5lY3QgdXNlciBpbnRlcmFjdGlvbiB3aXRoIGN1c3RvbSBwYWdpbmF0aW9uXG4gICAgICAkZWxlbS5maW5kKCcuc3dpcGVyLWN1c3RvbS1wYWdpbmF0aW9uIGxpJykub24oJ2NsaWNrJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICAgIHZhciAkZWxlbSA9ICQodGhpcykucGFyZW50cygnLnN3aXBlci1jb250YWluZXInKVxuICAgICAgICB2YXIgJHRhcmdldCA9ICQodGhpcylcbiAgICAgICAgdmFyIHN3aXBlciA9ICRlbGVtWzBdLnN3aXBlclxuICAgICAgICB2YXIgbmV3U2xpZGVJbmRleCA9ICRlbGVtLmZpbmQoJy5zd2lwZXItY3VzdG9tLXBhZ2luYXRpb24gbGknKS5pbmRleCgkdGFyZ2V0KVxuXG4gICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgICAgc3dpcGVyLnBhdXNlQXV0b3BsYXkoKVxuICAgICAgICBzd2lwZXIuc2xpZGVUbyhuZXdTbGlkZUluZGV4KVxuICAgICAgfSlcbiAgICB9XG5cbiAgICAvLyBTcGVjaWZpYyBzd2lwZXJzXG4gICAgLy8gLS0gSG9tZXBhZ2UgQWNxdWlzaXRpb24gVmlkZW8gSGVyb1xuICAgIGlmICgkZWxlbS5pcygnI2hvbWVhY3EtdmlkZW8taGVyby1zd2lwZXInKSkge1xuICAgICAgZWxlbVN3aXBlci5vbignc2xpZGVDaGFuZ2VTdGFydCcsIGZ1bmN0aW9uICgpIHtcbiAgICAgICAgdmFyIGVtcHJ1bnRlck5hbWUgPSAkZWxlbS5maW5kKCcuc3dpcGVyLXNsaWRlOmVxKCcgKyBlbGVtU3dpcGVyLmFjdGl2ZUluZGV4ICsgJyknKS5hdHRyKCdkYXRhLWVtcHJ1bnRlci1uYW1lJylcbiAgICAgICAgdmFyIHByZXRlck5hbWUgPSAkZWxlbS5maW5kKCcuc3dpcGVyLXNsaWRlOmVxKCcgKyBlbGVtU3dpcGVyLmFjdGl2ZUluZGV4ICsgJyknKS5hdHRyKCdkYXRhLXByZXRlci1uYW1lJylcbiAgICAgICAgaWYgKGVtcHJ1bnRlck5hbWUpICRlbGVtLnBhcmVudHMoJy5jdGEtdmlkZW8taGVybycpLmZpbmQoJy51aS1lbXBydW50ZXItbmFtZScpLnRleHQoZW1wcnVudGVyTmFtZSlcbiAgICAgICAgaWYgKHByZXRlck5hbWUpICRlbGVtLnBhcmVudHMoJy5jdGEtdmlkZW8taGVybycpLmZpbmQoJy51aS1wcmV0ZXItbmFtZScpLnRleHQocHJldGVyTmFtZSlcbiAgICAgIH0pXG4gICAgfVxuICB9KVxuXG5cbiAgLy8gQGRlYnVnIHJlbW92ZSBmb3IgcHJvZHVjdGlvblxuICAkZG9jXG4gICAgLm9uKFV0aWxpdHkuY2xpY2tFdmVudCwgJyNzZXQtbGFuZy1lbicsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgICAgJGh0bWwuYXR0cignbGFuZycsICdlbicpXG4gICAgICBfXy5kZWZhdWx0TGFuZyA9ICdlbidcbiAgICB9KVxuICAgIC5vbihVdGlsaXR5LmNsaWNrRXZlbnQsICcjc2V0LWxhbmctZW4tZ2InLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgICRodG1sLmF0dHIoJ2xhbmcnLCAnZW4tZ2InKVxuICAgICAgX18uZGVmYXVsdExhbmcgPSAnZW4tZ2InXG4gICAgfSlcbiAgICAub24oVXRpbGl0eS5jbGlja0V2ZW50LCAnI3NldC1sYW5nLWZyJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpXG4gICAgICAkaHRtbC5hdHRyKCdsYW5nJywgJ2ZyJylcbiAgICAgIF9fLmRlZmF1bHRMYW5nID0gJ2ZyJ1xuICAgIH0pXG4gICAgLm9uKFV0aWxpdHkuY2xpY2tFdmVudCwgJyNzZXQtbGFuZy1lcycsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgICAgJGh0bWwuYXR0cignbGFuZycsICdlcycpXG4gICAgICBfXy5kZWZhdWx0TGFuZyA9ICdlcydcbiAgICB9KVxuICAgIC5vbihVdGlsaXR5LmNsaWNrRXZlbnQsICcjcmVzdGFydC10ZXh0LWNvdW50ZXJzJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpXG4gICAgICAkKCcudWktdGV4dC1jb3VudCcpLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICAgICAgZWxlbS5UZXh0Q291bnQucmVzZXRDb3VudCgpXG4gICAgICAgIGVsZW0uVGV4dENvdW50LnN0YXJ0Q291bnQoKVxuICAgICAgfSlcbiAgICB9KVxuXG4gIC8qXG4gICAqIFRpbWUgY291bnRlcnNcbiAgICovXG4gICRkb2NcbiAgICAvLyBCYXNpYyBwcm9qZWN0IHRpbWUgY291bnRlclxuICAgIC5vbignVGltZUNvdW50OnVwZGF0ZScsICcudWktdGltZS1jb3VudGluZycsIGZ1bmN0aW9uIChldmVudCwgZWxlbVRpbWVDb3VudCwgdGltZVJlbWFpbmluZykge1xuICAgICAgdmFyICRlbGVtID0gJCh0aGlzKVxuICAgICAgdmFyIG91dHB1dFRpbWVcblxuICAgICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKHRpbWVSZW1haW5pbmcpXG5cbiAgICAgIGlmICh0aW1lUmVtYWluaW5nLmRheXMgPiAyKSB7XG4gICAgICAgIG91dHB1dFRpbWUgPSAodGltZVJlbWFpbmluZy5kYXlzICsgTWF0aC5jZWlsKHRpbWVSZW1haW5pbmcuaG91cnMgLyAyNCkpICsgJyAnICsgX18uX18oJ2RheXMnLCAndGltZUNvdW50RGF5cycpICsgJyAnICsgX18uX18oJ3JlbWFpbmluZycsICd0aW1lQ291bnRSZW1haW5pbmcnKVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgLy8gRXhwaXJlZFxuICAgICAgICBpZiAodGltZVJlbWFpbmluZy5zZWNvbmRzIDwgMCkge1xuICAgICAgICAgIG91dHB1dFRpbWUgPSBfXy5fXygnUHJvamVjdCBleHBpcmVkJywgJ3Byb2plY3RQZXJpb2RFeHBpcmVkJylcblxuICAgICAgICAvLyBDb3VudGRvd25cbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICBvdXRwdXRUaW1lID0gVXRpbGl0eS5sZWFkaW5nWmVybyh0aW1lUmVtYWluaW5nLmhvdXJzICsgKDI0ICogdGltZVJlbWFpbmluZy5kYXlzKSkgKyAnOicgKyBVdGlsaXR5LmxlYWRpbmdaZXJvKHRpbWVSZW1haW5pbmcubWludXRlcykgKyAnOicgKyBVdGlsaXR5LmxlYWRpbmdaZXJvKHRpbWVSZW1haW5pbmcuc2Vjb25kcylcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICAvLyBVcGRhdGUgY291bnRlclxuICAgICAgJGVsZW0udGV4dChvdXRwdXRUaW1lKVxuICAgIH0pXG5cbiAgICAvLyBQcm9qZWN0IGxpc3QgdGltZSBjb3VudHMgY29tcGxldGVkXG4gICAgLm9uKCdUaW1lQ291bnQ6Y29tcGxldGVkJywgJy5wcm9qZWN0LWxpc3QtaXRlbSAudWktdGltZS1jb3VudCcsIGZ1bmN0aW9uICgpIHtcbiAgICAgICQodGhpcykucGFyZW50cygnLnByb2plY3QtbGlzdC1pdGVtJykuYWRkQ2xhc3MoJ3VpLXByb2plY3QtZXhwaXJlZCcpXG4gICAgICAkKHRoaXMpLnRleHQoX18uX18oJ1Byb2plY3QgZXhwaXJlZCcsICdwcm9qZWN0TGlzdEl0ZW1QZXJpb2RFeHBpcmVkJykpXG4gICAgfSlcblxuICAgIC8vIFByb2plY3Qgc2luZ2xlIHRpbWUgY291bnQgY29tcGxldGVkXG4gICAgLm9uKCdUaW1lQ291bnQ6Y29tcGxldGVkJywgJy5wcm9qZWN0LXNpbmdsZSAudWktdGltZS1jb3VudCcsIGZ1bmN0aW9uICgpIHtcbiAgICAgICQodGhpcykucGFyZW50cygnLnByb2plY3Qtc2luZ2xlJykuYWRkQ2xhc3MoJ3VpLXByb2plY3QtZXhwaXJlZCcpXG4gICAgICAkKHRoaXMpLnRleHQoX18uX18oJ1Byb2plY3QgZXhwaXJlZCcsICdwcm9qZWN0U2luZ2xlUGVyaW9kRXhwaXJlZCcpKVxuICAgIH0pXG5cbiAgLypcbiAgICogV2F0Y2ggU2Nyb2xsXG4gICAqL1xuICAvLyBXaW5kb3cgc2Nyb2xsIHdhdGNoZXJcbiAgdmFyIHdhdGNoV2luZG93ID0gbmV3IFdhdGNoU2Nyb2xsLldhdGNoZXIod2luZG93KVxuICAgIC8vIEZpeCBzaXRlIG5hdlxuICAgIC53YXRjaCh3aW5kb3csICdzY3JvbGxUb3A+NTAnLCBmdW5jdGlvbiAoKSB7XG4gICAgICAkaHRtbC5hZGRDbGFzcygndWktc2l0ZS1oZWFkZXItZml4ZWQnKVxuICAgIH0pXG4gICAgLy8gVW5maXggc2l0ZSBuYXZcbiAgICAud2F0Y2god2luZG93LCAnc2Nyb2xsVG9wPD01MCcsIGZ1bmN0aW9uICgpIHtcbiAgICAgICRodG1sLnJlbW92ZUNsYXNzKCd1aS1zaXRlLWhlYWRlci1maXhlZCcpXG4gICAgfSlcbiAgICAvLyBTdGFydCB0ZXh0IGNvdW50ZXJzXG4gICAgLndhdGNoKCcudWktdGV4dC1jb3VudCcsICdlbnRlcicsIGZ1bmN0aW9uICgpIHtcbiAgICAgIGlmICh0aGlzLmhhc093blByb3BlcnR5KCdUZXh0Q291bnQnKSkge1xuICAgICAgICBpZiAoIXRoaXMuVGV4dENvdW50LnN0YXJ0ZWQoKSkgdGhpcy5UZXh0Q291bnQuc3RhcnRDb3VudCgpXG4gICAgICB9XG4gICAgfSlcblxuICAvLyBEeW5hbWljIHdhdGNoZXJzIChzaW5nbGUpXG4gIC8vIEBub3RlIGlmIHlvdSBuZWVkIHRvIGFkZCBtb3JlIHRoYW4gb25lIGFjdGlvbiwgSSBzdWdnZXN0IGRvaW5nIGl0IHZpYSBKU1xuICAkKCdbZGF0YS13YXRjaHNjcm9sbC1hY3Rpb25dJykuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgIHZhciAkZWxlbSA9ICQoZWxlbSlcbiAgICB2YXIgYWN0aW9uID0ge1xuICAgICAgYWN0aW9uOiAkZWxlbS5hdHRyKCdkYXRhLXdhdGNoc2Nyb2xsLWFjdGlvbicpLFxuICAgICAgY2FsbGJhY2s6ICRlbGVtLmF0dHIoJ2RhdGEtd2F0Y2hzY3JvbGwtY2FsbGJhY2snKSxcbiAgICAgIHRhcmdldDogJGVsZW0uYXR0cignZGF0YS13YXRjaHNjcm9sbC10YXJnZXQnKVxuICAgIH1cblxuICAgIC8vIERldGVjdCB3aGljaCBhY3Rpb24gYW5kIGNhbGxiYWNrIHRvIGZpcmVcbiAgICB3YXRjaFdpbmRvdy53YXRjaChlbGVtLCAkZWxlbS5hdHRyKCdkYXRhLXdhdGNoc2Nyb2xsLWFjdGlvbicpLCBmdW5jdGlvbiAoKSB7XG4gICAgICB3YXRjaFNjcm9sbENhbGxiYWNrLmFwcGx5KGVsZW0sIFthY3Rpb25dKVxuICAgIH0pXG4gIH0pXG5cbiAgLy8gQmFzaWMgV2F0Y2hTY3JvbGwgY2FsbGJhY2sgbWV0aG9kc1xuICBmdW5jdGlvbiB3YXRjaFNjcm9sbENhbGxiYWNrIChhY3Rpb24pIHtcbiAgICB2YXIgJGVsZW0gPSAkKHRoaXMpXG5cbiAgICAvLyBlLmcuIGBhZGRDbGFzczp1aS12aXNpYmxlYFxuICAgIHZhciBoYW5kbGUgPSBhY3Rpb24uY2FsbGJhY2tcbiAgICB2YXIgdGFyZ2V0ID0gYWN0aW9uLnRhcmdldCB8fCAkZWxlbVswXVxuICAgIHZhciBtZXRob2QgPSBoYW5kbGVcbiAgICB2YXIgdmFsdWVcbiAgICBpZiAoIWhhbmRsZSkgcmV0dXJuXG5cbiAgICAvLyBTcGxpdCB0byBnZXQgb3RoZXIgdmFsdWVzXG4gICAgaWYgKC9cXDovLnRlc3QoaGFuZGxlKSkge1xuICAgICAgaGFuZGxlID0gaGFuZGxlLnNwbGl0KCc6JylcbiAgICAgIG1ldGhvZCA9IGhhbmRsZVswXVxuICAgICAgdmFsdWUgPSBoYW5kbGVbMV1cbiAgICB9XG5cbiAgICAvLyBHZXQgdGhlIHRhcmdldFxuICAgICR0YXJnZXQgPSAkKHRhcmdldClcblxuICAgIC8vIEBkZWJ1ZyBjb25zb2xlLmxvZygnd2F0Y2hTY3JvbGxDYWxsYmFjaycsIHRoaXMsIG1ldGhvZCwgdmFsdWUpO1xuXG4gICAgLy8gSGFuZGxlIGRpZmZlcmVudCBtZXRob2RzXG4gICAgc3dpdGNoIChtZXRob2QudG9Mb3dlckNhc2UoKSkge1xuICAgICAgLy8gYWRkY2xhc3M6Y2xhc3MtdG8tYWRkXG4gICAgICBjYXNlICdhZGRjbGFzcyc6XG4gICAgICAgICR0YXJnZXQuYWRkQ2xhc3ModmFsdWUpXG4gICAgICAgIGJyZWFrXG5cbiAgICAgIC8vIHJlbW92ZWNsYXNzOmNsYXNzLXRvLXJlbW92ZVxuICAgICAgY2FzZSAncmVtb3ZlY2xhc3MnOlxuICAgICAgICAkdGFyZ2V0LnJlbW92ZUNsYXNzKHZhbHVlKVxuICAgICAgICBicmVha1xuICAgIH1cbiAgfVxuXG4gIC8qXG4gICAqIFdhdGNoU2Nyb2xsIE5hdjogSWYgaXRlbSBpcyB2aXNpYmxlICh2aWEgV2F0Y2hTY3JvbGwgYWN0aW9uIGBlbnRlcmApIHRoZW4gbWFrZSB0aGUgbmF2aWdhdGlvbiBpdGVtIGFjdGl2ZVxuICAgKi9cbiAgJCgnW2RhdGEtd2F0Y2hzY3JvbGwtbmF2XScpLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICB3YXRjaFdpbmRvdy53YXRjaChlbGVtLCBXYXRjaFNjcm9sbC5hY3Rpb25zLndpdGhpbk1pZGRsZSlcbiAgfSlcbiAgJGRvYy5vbignd2F0Y2hzY3JvbGwtYWN0aW9uLXdpdGhpbm1pZGRsZScsICdbZGF0YS13YXRjaHNjcm9sbC1uYXZdJywgZnVuY3Rpb24gKCkge1xuICAgIHZhciAkbmF2TGlua3MgPSAkKCcubmF2IGxpOm5vdChcIi5hY3RpdmVcIikgYVtocmVmPVwiIycgKyAkKHRoaXMpLmF0dHIoJ2lkJykgKyAnXCJdJylcbiAgICAkbmF2TGlua3MuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgICAgdmFyICRlbGVtID0gJChlbGVtKVxuICAgICAgdmFyICRuYXZJdGVtID0gJGVsZW0ucGFyZW50cygnbGknKS5maXJzdCgpXG4gICAgICBpZiAoISRuYXZJdGVtLmlzKCcuYWN0aXZlJykpIHtcbiAgICAgICAgJGVsZW0ucGFyZW50cygnLm5hdicpLmZpcnN0KCkuZmluZCgnbGknKS5yZW1vdmVDbGFzcygnYWN0aXZlJykuZmlsdGVyKCRuYXZJdGVtKS5hZGRDbGFzcygnYWN0aXZlJylcbiAgICAgIH1cbiAgICB9KVxuICB9KVxuXG4gIC8qXG4gICAqIEZpeGVkIHByb2plY3Qgc2luZ2xlIG1lbnVcbiAgICovXG4gIHZhciBwcm9qZWN0U2luZ2xlTmF2T2Zmc2V0VG9wXG4gIGlmICgkKCcucHJvamVjdC1zaW5nbGUtbWVudScpLmxlbmd0aCA+IDApIHtcbiAgICBwcm9qZWN0U2luZ2xlTmF2T2Zmc2V0VG9wID0gJCgnLnByb2plY3Qtc2luZ2xlLW5hdicpLmZpcnN0KCkub2Zmc2V0KCkudG9wIC0gKHBhcnNlSW50KCQoJy5zaXRlLWhlYWRlcicpLmhlaWdodCgpLCAxMCkgKiAwLjUpXG4gICAgd2F0Y2hXaW5kb3dcbiAgICAgIC53YXRjaCh3aW5kb3csIGZ1bmN0aW9uIChwYXJhbXMpIHtcbiAgICAgICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCR3aW4uc2Nyb2xsVG9wKCkgPj0gcHJvamVjdFNpbmdsZU5hdk9mZnNldFRvcClcbiAgICAgICAgaWYgKCR3aW4uc2Nyb2xsVG9wKCkgPj0gcHJvamVjdFNpbmdsZU5hdk9mZnNldFRvcCkge1xuICAgICAgICAgICRodG1sLmFkZENsYXNzKCd1aS1wcm9qZWN0LXNpbmdsZS1tZW51LWZpeGVkJylcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAkaHRtbC5yZW1vdmVDbGFzcygndWktcHJvamVjdC1zaW5nbGUtbWVudS1maXhlZCcpXG4gICAgICAgIH1cbiAgICAgIH0pXG4gIH1cblxuICAvKlxuICAgKiBQcm9ncmVzcyB0YWJzXG4gICAqL1xuICAvLyBBbnkgdGFicyBhcmVhcyB3aXRoIGAudWktdGFicy1wcm9ncmVzc2AgY2xhc3Mgd2lsbCBhZGQgYSBgLmNvbXBsZXRlYCBjbGFzcyB0byB0aGUgdGFicyBiZWZvcmVcbiAgJGRvYy5vbignc2hvd24uYnMudGFiJywgJy50YWJzLnVpLXRhYnMtcHJvZ3Jlc3MnLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICB2YXIgJHRhcmdldCA9ICQoZXZlbnQudGFyZ2V0KVxuICAgIHZhciAkdGFiID0gJCgnLm5hdiBhW3JvbGU9XCJ0YWJcIl1baHJlZj1cIicgKyAkdGFyZ2V0LmF0dHIoJ2hyZWYnKSArICdcIl0nKS5maXJzdCgpXG4gICAgdmFyICRuYXYgPSAkdGFiLnBhcmVudHMoJy5uYXYnKVxuICAgIHZhciAkdGFicyA9ICRuYXYuZmluZCgnYVtyb2xlPVwidGFiXCJdJylcbiAgICB2YXIgdGFiSW5kZXggPSAkdGFicy5pbmRleCgkdGFiKVxuXG4gICAgaWYgKHRhYkluZGV4ID49IDApIHtcbiAgICAgICR0YWJzLmZpbHRlcignOmd0KCcrdGFiSW5kZXgrJyknKS5wYXJlbnRzKCdsaScpLnJlbW92ZUNsYXNzKCdhY3RpdmUgY29tcGxldGUnKVxuICAgICAgJHRhYnMuZmlsdGVyKCc6bHQoJyt0YWJJbmRleCsnKScpLnBhcmVudHMoJ2xpJykucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpLmFkZENsYXNzKCdjb21wbGV0ZScpXG4gICAgICAkdGFiLnBhcmVudHMoJ2xpJykucmVtb3ZlQ2xhc3MoJ2NvbXBsZXRlJykuYWRkQ2xhc3MoJ2FjdGl2ZScpXG4gICAgfVxuICB9KVxuXG4gIC8vIFZhbGlkYXRlIGFueSBncm91cHMvZmllbGRzIHdpdGhpbiB0aGUgdGFiYmVkIGFyZWEgYmVmb3JlIGdvaW5nIG9uIHRvIHRoZSBuZXh0IHN0YWdlXG4gICRkb2Mub24oJ3Nob3cuYnMudGFiJywgJy50YWJzLnVpLXRhYnMtcHJvZ3Jlc3MgW3JvbGU9XCJ0YWJcIl0nLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICB2YXIgJGZvcm0gPSBVdGlsaXR5LmdldEVsZW1Jc09ySGFzUGFyZW50KGV2ZW50LnRhcmdldCwgJ2Zvcm0nKS5maXJzdCgpXG4gICAgdmFyICRuZXh0VGFiID0gJCgkKGV2ZW50LnRhcmdldCkuYXR0cignaHJlZicpKVxuICAgIHZhciAkY3VycmVudFRhYiA9ICRmb3JtLmZpbmQoJ1tyb2xlPVwidGFicGFuZWxcIl0uYWN0aXZlJykuZmlyc3QoKVxuXG4gICAgLy8gY29uc29sZS5sb2coJGZvcm0sICRjdXJyZW50VGFiKVxuXG4gICAgLy8gVmFsaWRhdGUgdGhlIGZvcm0gd2l0aGluIHRoZSBjdXJyZW50IHRhYiBiZWZvcmUgY29udGludWluZ1xuICAgIGlmICgkY3VycmVudFRhYi5maW5kKCdbZGF0YS1mb3JtdmFsaWRhdGlvbl0nKS5sZW5ndGggPiAwKSB7XG4gICAgICB2YXIgZmEgPSAkY3VycmVudFRhYi5maW5kKCdbZGF0YS1mb3JtdmFsaWRhdGlvbl0nKS5maXJzdCgpWzBdLkZvcm1WYWxpZGF0aW9uXG4gICAgICB2YXIgZm9ybVZhbGlkYXRpb24gPSBmYS52YWxpZGF0ZSgpXG4gICAgICBjb25zb2xlLmxvZyhmb3JtVmFsaWRhdGlvbilcblxuICAgICAgLy8gVmFsaWRhdGlvbiBFcnJvcnM6IHByZXZlbnQgZ29pbmcgdG8gdGhlIG5leHQgdGFiXG4gICAgICBpZiAoZm9ybVZhbGlkYXRpb24uZXJyb3JlZEZpZWxkcy5sZW5ndGggPiAwKSB7XG4gICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgICAgZXZlbnQuc3RvcFByb3BhZ2F0aW9uKClcbiAgICAgICAgc2Nyb2xsVG8oZmEuJG5vdGlmaWNhdGlvbnMpXG4gICAgICAgIHJldHVybiBmYWxzZVxuICAgICAgfVxuICAgIH1cbiAgfSlcblxuICAvKlxuICAgKiBFbXBydW50ZXIgU2ltXG4gICAqL1xuICAkZG9jXG4gICAgLm9uKCdzaG93bi5icy50YWInLCAnLmVtcHJ1bnRlci1zaW0nLCBmdW5jdGlvbiAoKSB7XG4gICAgICBjb25zb2xlLmxvZygnc2hvd24gdGFiJylcbiAgICB9KVxuICAgIC8vIFN0ZXAgMVxuICAgIC5vbignRm9ybVZhbGlkYXRpb246dmFsaWRhdGU6ZXJyb3InLCAnI2VzaW0xJywgZnVuY3Rpb24gKCkge1xuICAgICAgLy8gSGlkZSB0aGUgY29udGludWUgYnV0dG9uXG4gICAgICAkKCcuZW1wcnVudGVyLXNpbScpLnJlbW92ZUNsYXNzKCd1aS1lbXBydW50ZXItc2ltLWVzdGltYXRlLXNob3cnKVxuICAgIH0pXG4gICAgLm9uKCdGb3JtVmFsaWRhdGlvbjp2YWxpZGF0ZTpzdWNjZXNzJywgJyNlc2ltMScsIGZ1bmN0aW9uICgpIHtcbiAgICAgIC8vIFNob3cgdGhlIGNvbnRpbnVlIGJ1dHRvblxuICAgICAgJCgnLmVtcHJ1bnRlci1zaW0nKS5hZGRDbGFzcygndWktZW1wcnVudGVyLXNpbS1lc3RpbWF0ZS1zaG93JylcbiAgICB9KVxuICAgIC5vbignY2hhbmdlJywgJ2Zvcm0uZW1wcnVudGVyLXNpbScsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgY29uc29sZS5sb2coZXZlbnQudHlwZSwgZXZlbnQudGFyZ2V0KVxuICAgIH0pXG4gICAgLy8gU3RlcCAyXG4gICAgLy8gLm9uKCdGb3JtVmFsaWRhdGlvbjp2YWxpZGF0ZTplcnJvcicsICcjZXNpbTInLCBmdW5jdGlvbiAoKSB7XG4gICAgLy8gICAvLyBIaWRlIHRoZSBzdWJtaXQgYnV0dG9uXG4gICAgLy8gICAkKCcuZW1wcnVudGVyLXNpbScpLnJlbW92ZUNsYXNzKCd1aS1lbXBydW50ZXItc2ltLXN0ZXAtMicpXG4gICAgLy8gfSlcbiAgICAvLyAub24oJ0Zvcm1WYWxpZGF0aW9uOnZhbGlkYXRlOnN1Y2Nlc3MnLCAnI2VzaW0yJywgZnVuY3Rpb24gKCkge1xuICAgIC8vICAgLy8gU2hvdyB0aGUgc3VibWl0IGJ1dHRvblxuICAgIC8vICAgJCgnLmVtcHJ1bnRlci1zaW0nKS5yZW1vdmVDbGFzcygndWktZW1wcnVudGVyLXNpbS1zdGVwLTEnKS5hZGRDbGFzcygndWktZW1wcnVudGVyLXNpbS1zdGVwLTInKVxuICAgIC8vIH0pXG5cbiAgLypcbiAgICogUHJvamVjdCBMaXN0XG4gICAqL1xuICAvLyBTZXQgb3JpZ2luYWwgb3JkZXIgZm9yIGVhY2ggbGlzdFxuICAkKCcucHJvamVjdC1saXN0JykuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgIHZhciAkZWxlbSA9ICQoZWxlbSlcbiAgICB2YXIgJGl0ZW1zID0gJGVsZW0uZmluZCgnLnByb2plY3QtbGlzdC1pdGVtJylcblxuICAgICRpdGVtcy5lYWNoKGZ1bmN0aW9uIChqLCBpdGVtKSB7XG4gICAgICAkKGl0ZW0pLmF0dHIoJ2RhdGEtb3JpZ2luYWwtb3JkZXInLCBqKVxuICAgIH0pXG4gIH0pXG5cbiAgLy8gUmVvcmRlciBwcm9qZWN0IGxpc3QgZGVwZW5kaW5nIG9uIGZpbHRlcmVkIGNvbHVtbiBhbmQgc29ydCBkaXJlY3Rpb25cbiAgJGRvYy5vbihVdGlsaXR5LmNsaWNrRXZlbnQsICcucHJvamVjdC1saXN0LWZpbHRlcltkYXRhLXNvcnQtYnldJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgdmFyICRlbGVtID0gJCh0aGlzKVxuICAgIHZhciAkcHJvamVjdExpc3QgPSAkZWxlbS5wYXJlbnRzKCcucHJvamVjdC1saXN0JylcbiAgICB2YXIgJGZpbHRlcnMgPSAkcHJvamVjdExpc3QuZmluZCgnLnByb2plY3QtbGlzdC1maWx0ZXInKVxuICAgIHZhciAkbGlzdCA9ICRwcm9qZWN0TGlzdC5maW5kKCcucHJvamVjdC1saXN0LWl0ZW1zJylcbiAgICB2YXIgJGl0ZW1zID0gJGxpc3QuZmluZCgnLnByb2plY3QtbGlzdC1pdGVtJylcbiAgICB2YXIgc29ydENvbHVtbiA9IGZhbHNlXG4gICAgdmFyIHNvcnREaXJlY3Rpb24gPSBmYWxzZVxuICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcblxuICAgIC8vIEdldCBjb2x1bW4gdG8gc29ydCBieVxuICAgIHNvcnRDb2x1bW4gPSAkZWxlbS5hdHRyKCdkYXRhLXNvcnQtYnknKVxuXG4gICAgLy8gR2V0IGRpcmVjdGlvbiB0byBzb3J0IGJ5XG4gICAgaWYgKCRlbGVtLmlzKCcudWktcHJvamVjdC1saXN0LXNvcnQtYXNjJykpIHtcbiAgICAgIHNvcnREaXJlY3Rpb24gPSAnZGVzYydcbiAgICB9IGVsc2Uge1xuICAgICAgc29ydERpcmVjdGlvbiA9ICdhc2MnXG4gICAgfVxuXG4gICAgLy8gRXJyb3IgaWYgaW52YWxpZCB2YWx1ZXNcbiAgICBpZiAoIXNvcnRDb2x1bW4gfHwgIXNvcnREaXJlY3Rpb24pIHJldHVyblxuXG4gICAgLy8gUmVzZXQgYWxsIHNvcnRpbmcgZmlsdGVyc1xuICAgICRmaWx0ZXJzLnJlbW92ZUNsYXNzKCd1aS1wcm9qZWN0LWxpc3Qtc29ydC1hc2MgdWktcHJvamVjdC1saXN0LXNvcnQtZGVzYycpXG5cbiAgICAvLyBTb3J0aW5nXG4gICAgc3dpdGNoIChzb3J0RGlyZWN0aW9uKSB7XG4gICAgICBjYXNlICdhc2MnOlxuICAgICAgICAkaXRlbXMuc29ydChmdW5jdGlvbiAoYSwgYikge1xuICAgICAgICAgIGEgPSBwYXJzZUZsb2F0KCQoYSkuYXR0cignZGF0YS1zb3J0LScgKyBzb3J0Q29sdW1uKSlcbiAgICAgICAgICBiID0gcGFyc2VGbG9hdCgkKGIpLmF0dHIoJ2RhdGEtc29ydC0nICsgc29ydENvbHVtbikpXG4gICAgICAgICAgc3dpdGNoIChhID4gYikge1xuICAgICAgICAgICAgY2FzZSB0cnVlOlxuICAgICAgICAgICAgICByZXR1cm4gMVxuICAgICAgICAgICAgY2FzZSBmYWxzZTpcbiAgICAgICAgICAgICAgcmV0dXJuIC0xXG4gICAgICAgICAgICBkZWZhdWx0OlxuICAgICAgICAgICAgICByZXR1cm4gMFxuICAgICAgICAgIH1cbiAgICAgICAgfSlcbiAgICAgICAgYnJlYWtcblxuICAgICAgY2FzZSAnZGVzYyc6XG4gICAgICAgICRpdGVtcy5zb3J0KGZ1bmN0aW9uIChhLCBiKSB7XG4gICAgICAgICAgYSA9IHBhcnNlRmxvYXQoJChhKS5hdHRyKCdkYXRhLXNvcnQtJyArIHNvcnRDb2x1bW4pKVxuICAgICAgICAgIGIgPSBwYXJzZUZsb2F0KCQoYikuYXR0cignZGF0YS1zb3J0LScgKyBzb3J0Q29sdW1uKSlcbiAgICAgICAgICBzd2l0Y2ggKGEgPCBiKSB7XG4gICAgICAgICAgICBjYXNlIHRydWU6XG4gICAgICAgICAgICAgIHJldHVybiAxXG4gICAgICAgICAgICBjYXNlIGZhbHNlOlxuICAgICAgICAgICAgICByZXR1cm4gLTFcbiAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgIHJldHVybiAwXG4gICAgICAgICAgfVxuICAgICAgICB9KVxuICAgICAgICBicmVha1xuICAgIH1cblxuICAgIC8vIFNldCBzb3J0ZWQgY29sdW1uIHRvIHNvcnQgZGlyZWN0aW9uIGNsYXNzXG4gICAgJGVsZW0uYWRkQ2xhc3MoJ3VpLXByb2plY3QtbGlzdC1zb3J0LScgKyBzb3J0RGlyZWN0aW9uKVxuXG4gICAgLy8gQ2hhbmdlIHRoZSBET00gb3JkZXIgb2YgaXRlbXNcbiAgICAkaXRlbXMuZGV0YWNoKCkuYXBwZW5kVG8oJGxpc3QpXG4gIH0pXG5cbiAgLypcbiAgICogVGVzdCBmb3IgSUVcbiAgICovXG4gIGZ1bmN0aW9uIGlzSUUgKHZlcnNpb24pIHtcbiAgICB2YXIgdmVyc2lvbk51bSA9IH5+KHZlcnNpb24gKyAnJy5yZXBsYWNlKC9cXEQrL2csICcnKSlcbiAgICBpZiAoL15cXDwvLnRlc3QodmVyc2lvbikpIHtcbiAgICAgIHZlcnNpb24gPSAnbHQtaWUnICsgdmVyc2lvbk51bVxuICAgIH0gZWxzZSB7XG4gICAgICB2ZXJzaW9uID0gJ2llJyArIHZlcnNpb25OdW1cbiAgICB9XG4gICAgcmV0dXJuICRodG1sLmlzKCcuJyArIHZlcnNpb24pXG4gIH1cblxuICAvKlxuICAgKiBJIGhhdGUgSUVcbiAgICovXG4gIGlmIChpc0lFKDkpKSB7XG4gICAgLy8gU3BlY2lmaWMgZml4ZXMgZm9yIElFXG4gICAgJCgnLnByb2plY3QtbGlzdC1pdGVtIC5wcm9qZWN0LWxpc3QtaXRlbS1jYXRlZ29yeScpLmVhY2goZnVuY3Rpb24gKGksIGl0ZW0pIHtcbiAgICAgICQodGhpcykud3JhcElubmVyKCc8ZGl2IHN0eWxlPVwid2lkdGg6IDEwMCU7IGhlaWdodDogMTAwJTsgcG9zaXRpb246IHJlbGF0aXZlXCI+PC9kaXY+JylcbiAgICB9KVxuICB9XG5cbiAgLypcbiAgICogUmVzcG9uc2l2ZVxuICAgKi9cbiAgLy8gR2V0IGJyZWFrcG9pbnRzXG4gIC8vIEBtZXRob2QgZ2V0QWN0aXZlQnJlYWtwb2ludHNcbiAgLy8gQHJldHVybnMge1N0cmluZ31cbiAgZnVuY3Rpb24gZ2V0QWN0aXZlQnJlYWtwb2ludHMoKSB7XG4gICAgdmFyIHdpZHRoID0gd2luZG93LmlubmVyV2lkdGg7XG4gICAgdmFyIGJwID0gW11cbiAgICBmb3IgKHZhciB4IGluIGJyZWFrcG9pbnRzKSB7XG4gICAgICBpZiAoIHdpZHRoID49IGJyZWFrcG9pbnRzW3hdWzBdICYmIHdpZHRoIDw9IGJyZWFrcG9pbnRzW3hdWzFdKSBicC5wdXNoKHgpXG4gICAgfVxuICAgIHJldHVybiBicC5qb2luKCcgJylcbiAgfVxuXG4gIC8qXG4gICAqIFNldCB0byBkZXZpY2UgaGVpZ2h0XG4gICAqIFJlbGllcyBvbiBlbGVtZW50IHRvIGhhdmUgW2RhdGEtc2V0LWRldmljZS1oZWlnaHRdIGF0dHJpYnV0ZSBzZXRcbiAgICogdG8gb25lIG9yIG1hbnkgYnJlYWtwb2ludCBuYW1lcywgZS5nLiBgZGF0YS1zZXQtZGV2aWNlLWhlaWdodD1cInhzIHNtXCJgXG4gICAqIGZvciBkZXZpY2UncyBoZWlnaHQgdG8gYmUgYXBwbGllZCBhdCB0aG9zZSBicmVha3BvaW50c1xuICAgKi9cbiAgZnVuY3Rpb24gc2V0RGV2aWNlSGVpZ2h0cygpIHtcbiAgICAvLyBBbHdheXMgZ2V0IHRoZSBzaXRlIGhlYWRlciBoZWlnaHQgdG8gcmVtb3ZlIGZyb20gdGhlIGVsZW1lbnQncyBoZWlnaHRcbiAgICB2YXIgc2l0ZUhlYWRlckhlaWdodCA9ICQoJy5zaXRlLWhlYWRlcicpLm91dGVySGVpZ2h0KClcbiAgICB2YXIgZGV2aWNlSGVpZ2h0ID0gd2luZG93LmlubmVySGVpZ2h0IC0gc2l0ZUhlYWRlckhlaWdodFxuXG4gICAgLy8gU2V0IGVsZW1lbnQgdG8gaGVpZ2h0IG9mIGRldmljZVxuICAgICQoJ1tkYXRhLXNldC1kZXZpY2UtaGVpZ2h0XScpLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICAgIHZhciAkZWxlbSA9ICQoZWxlbSlcbiAgICAgIHZhciBjaGVja0JwID0gJGVsZW0uYXR0cignZGF0YS1zZXQtZGV2aWNlLWhlaWdodCcpLnRyaW0oKS50b0xvd2VyQ2FzZSgpXG4gICAgICB2YXIgc2V0SGVpZ2h0ID0gZmFsc2VcblxuICAgICAgLy8gVHVybiBlbGVtIHNldHRpbmcgaW50byBhbiBhcnJheSB0byBpdGVyYXRlIG92ZXIgbGF0ZXJcbiAgICAgIGlmICghL1ssIF0vLnRlc3QoY2hlY2tCcCkpIHtcbiAgICAgICAgY2hlY2tCcCA9IFtjaGVja0JwXVxuICAgICAgfSBlbHNlIHtcbiAgICAgICAgY2hlY2tCcCA9IGNoZWNrQnAuc3BsaXQoL1ssIF0rLylcbiAgICAgIH1cblxuICAgICAgLy8gQ2hlY2sgaWYgZWxlbSBzaG91bGQgYmUgc2V0IHRvIGRldmljZSdzIGhlaWdodFxuICAgICAgZm9yICh2YXIgaiBpbiBjaGVja0JwKSB7XG4gICAgICAgIGlmIChuZXcgUmVnRXhwKGNoZWNrQnBbal0sICdpJykudGVzdChjdXJyZW50QnJlYWtwb2ludCkpIHtcbiAgICAgICAgICBzZXRIZWlnaHQgPSBjaGVja0JwW2pdXG4gICAgICAgICAgYnJlYWtcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICAvLyBTZXQgdGhlIGhlaWdodFxuICAgICAgaWYgKHNldEhlaWdodCkge1xuICAgICAgICAvLyBAZGVidWdcbiAgICAgICAgLy8gY29uc29sZS5sb2coJ1NldHRpbmcgZWxlbWVudCBoZWlnaHQgdG8gZGV2aWNlJywgY3VycmVudEJyZWFrcG9pbnQsIGNoZWNrQnApXG4gICAgICAgICRlbGVtLmNzcygnaGVpZ2h0JywgZGV2aWNlSGVpZ2h0ICsgJ3B4JykuYWRkQ2xhc3MoJ3VpLXNldC1kZXZpY2UtaGVpZ2h0JylcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgICRlbGVtLmNzcygnaGVpZ2h0JywgJycpLnJlbW92ZUNsYXNzKCd1aS1zZXQtZGV2aWNlLWhlaWdodCcpXG4gICAgICB9XG4gICAgfSlcbiAgfVxuXG4gIC8qXG4gICAqIEVxdWFsIEhlaWdodFxuICAgKiBTZXRzIG11bHRpcGxlIGVsZW1lbnRzIHRvIGJlIHRoZSBlcXVhbCAobWF4aW11bSkgaGVpZ2h0XG4gICAqIEVsZW1lbnRzIHJlcXVpcmUgYXR0cmlidXRlIFtkYXRhLWVxdWFsLWhlaWdodF0gc2V0LiBZb3UgY2FuIGFsc28gc3BlY2lmeSB0aGVcbiAgICogYnJlYWtwb2ludHMgeW91IG9ubHkgd2FudCB0aGlzIHRvIGJlIGFwcGxpZWQgdG8gaW4gdGhpcyBhdHRyaWJ1dGUsIGUuZy5cbiAgICogYDxkaXYgZGF0YS1lcXVhbC1oZWlnaHQ9XCJ4c1wiPi4uPC9kaXY+YCB3b3VsZCBvbmx5IGJlIGFwcGxpZWQgaW4gYHhzYCBicmVha3BvaW50XG4gICAqIElmIHlvdSB3YW50IHRvIHNlcGFyYXRlIGVxdWFsIGhlaWdodCBlbGVtZW50cyBpbnRvIGdyb3VwcywgYWRkaXRpb25hbGx5XG4gICAqIHNldCB0aGUgW2RhdGEtZXF1YWwtaGVpZ2h0LWdyb3VwXSBhdHRyaWJ1dGUgdG8gYSB1bmlxdWUgc3RyaW5nIElELCBlLmcuXG4gICAqIGA8ZGl2IGRhdGEtZXF1YWwtaGVpZ2h0PVwieHNcIiBkYXRhLWVxdWFsLWhlaWdodC1ncm91cD1cInByb21vMVwiPi4uPC9kaXY+YFxuICAgKi9cbiAgZnVuY3Rpb24gc2V0RXF1YWxIZWlnaHRzICgpIHtcbiAgICB2YXIgZXF1YWxIZWlnaHRzID0ge31cbiAgICAkKCdbZGF0YS1lcXVhbC1oZWlnaHRdJykuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgICAgdmFyICRlbGVtID0gJChlbGVtKVxuICAgICAgdmFyIGdyb3VwTmFtZSA9ICRlbGVtLmF0dHIoJ2RhdGEtZXF1YWwtaGVpZ2h0LWdyb3VwJykgfHwgJ2RlZmF1bHQnXG4gICAgICB2YXIgZWxlbUhlaWdodCA9ICRlbGVtLmNzcygnaGVpZ2h0JywgJycpLm91dGVySGVpZ2h0KClcblxuICAgICAgLy8gQ3JlYXRlIHZhbHVlIHRvIHNhdmUgbWF4IGhlaWdodCB0b1xuICAgICAgaWYgKCFlcXVhbEhlaWdodHMuaGFzT3duUHJvcGVydHkoZ3JvdXBOYW1lKSkgZXF1YWxIZWlnaHRzW2dyb3VwTmFtZV0gPSAwXG5cbiAgICAgIC8vIFNldCBtYXggaGVpZ2h0XG4gICAgICBpZiAoZWxlbUhlaWdodCA+IGVxdWFsSGVpZ2h0c1tncm91cE5hbWVdKSBlcXVhbEhlaWdodHNbZ3JvdXBOYW1lXSA9IGVsZW1IZWlnaHRcblxuICAgIC8vIEFmdGVyIHByb2Nlc3NpbmcgYWxsLCBhcHBseSBoZWlnaHQgKGRlcGVuZGluZyBvbiBicmVha3BvaW50KVxuICAgIH0pLmVhY2goZnVuY3Rpb24gKGksIGVsZW0pIHtcbiAgICAgIHZhciAkZWxlbSA9ICQoZWxlbSlcbiAgICAgIHZhciBncm91cE5hbWUgPSAkZWxlbS5hdHRyKCdkYXRhLWVxdWFsLWhlaWdodC1ncm91cCcpIHx8ICdkZWZhdWx0J1xuICAgICAgdmFyIGFwcGx5VG9CcCA9ICRlbGVtLmF0dHIoJ2RhdGEtZXF1YWwtaGVpZ2h0JylcblxuICAgICAgLy8gT25seSBhcHBseSB0byBjZXJ0YWluIGJyZWFrcG9pbnRzXG4gICAgICBpZiAoYXBwbHlUb0JwKSB7XG4gICAgICAgIGFwcGx5VG9CcCA9IGFwcGx5VG9CcC5zcGxpdCgvWyAsXSsvKVxuXG4gICAgICAgIC8vIFRlc3QgYnJlYWtwb2ludFxuICAgICAgICBpZiAobmV3IFJlZ0V4cChhcHBseVRvQnAuam9pbignfCcpLCAnaScpLnRlc3QoZ2V0QWN0aXZlQnJlYWtwb2ludHMoKSkpIHtcbiAgICAgICAgICAkZWxlbS5oZWlnaHQoZXF1YWxIZWlnaHRzW2dyb3VwTmFtZV0pXG5cbiAgICAgICAgLy8gUmVtb3ZlIGhlaWdodFxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICRlbGVtLmNzcygnaGVpZ2h0JywgJycpXG4gICAgICAgIH1cblxuICAgICAgLy8gTm8gYnJlYWtwb2ludCBzZXQ/IEFwcGx5IGluZGlzY3JpbWluYXRlbHlcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgICRlbGVtLmhlaWdodChlcXVhbEhlaWdodHNbZ3JvdXBOYW1lXSlcbiAgICAgIH1cbiAgICB9KVxuICB9XG5cbiAgLypcbiAgICogVXBkYXRlIFdpbmRvd1xuICAgKi9cbiAgLy8gUGVyZm9ybSBhY3Rpb25zIHdoZW4gdGhlIHdpbmRvdyBuZWVkcyB0byBiZSB1cGRhdGVkXG4gIGZ1bmN0aW9uIHVwZGF0ZVdpbmRvdygpIHtcbiAgICBjbGVhclRpbWVvdXQodGltZXJEZWJvdW5jZVJlc2l6ZSlcblxuICAgIC8vIEdldCBhY3RpdmUgYnJlYWtwb2ludHNcbiAgICBjdXJyZW50QnJlYWtwb2ludCA9IGdldEFjdGl2ZUJyZWFrcG9pbnRzKClcblxuICAgIC8vIFVwZGF0ZSB0aGUgcG9zaXRpb24gb2YgdGhlIHByb2plY3Qtc2luZ2xlLW1lbnUgdG9wIG9mZnNldFxuICAgIGlmICghJGh0bWwuaXMoJy51aS1wcm9qZWN0LXNpbmdsZS1tZW51LWZpeGVkJykgJiYgdHlwZW9mIHByb2plY3RTaW5nbGVOYXZPZmZzZXRUb3AgIT09ICd1bmRlZmluZWQnKSB7XG4gICAgICBwcm9qZWN0U2luZ2xlTmF2T2Zmc2V0VG9wID0gJCgnLnByb2plY3Qtc2luZ2xlLW5hdicpLmZpcnN0KCkub2Zmc2V0KCkudG9wIC0gKHBhcnNlSW50KCQoJy5zaXRlLWhlYWRlcicpLmhlaWdodCgpLCAxMCkgKiAwLjUpXG4gICAgfVxuXG4gICAgLy8gVXBkYXRlIHRoZSBwb3NpdGlvbiBvZiB0aGUgcHJvamVjdC1zaW5nbGUtaW5mbyBvZmZzZXRcbiAgICBvZmZzZXRQcm9qZWN0U2luZ2xlSW5mbygpXG5cbiAgICAvLyBTZXQgZGV2aWNlIGhlaWdodHNcbiAgICBzZXREZXZpY2VIZWlnaHRzKClcblxuICAgIC8vIFVwZGF0ZSBlcXVhbCBoZWlnaHRzXG4gICAgc2V0RXF1YWxIZWlnaHRzKClcbiAgfVxuXG4gIC8vIFNjcm9sbCB0aGUgd2luZG93IHRvIGEgcG9pbnQsIG9yIGFuIGVsZW1lbnQgb24gdGhlIHBhZ2VcbiAgZnVuY3Rpb24gc2Nyb2xsVG8gKHBvaW50LCBjYiwgdGltZSkge1xuICAgIC8vIEdldCBlbGVtZW50IHRvIHNjcm9sbCB0b29cbiAgICB2YXIgJGVsZW0gPSAkKHBvaW50KVxuICAgIHZhciB3aW5TY3JvbGxUb3AgPSAkd2luLnNjcm9sbFRvcCgpXG4gICAgdmFyIHRvU2Nyb2xsVG9wID0gMFxuICAgIHZhciBkaWZmXG5cbiAgICAvLyBUcnkgbnVtZXJpYyB2YWx1ZVxuICAgIGlmICgkZWxlbS5sZW5ndGggPT09IDApIHtcbiAgICAgIHRvU2Nyb2xsVG9wID0gcGFyc2VJbnQocG9pbnQsIDEwKVxuICAgIH0gZWxzZSB7XG4gICAgICB0b1Njcm9sbFRvcCA9ICRlbGVtLmVxKDApLm9mZnNldCgpLnRvcCAtIDgwIC8vIEZpeGVkIGhlYWRlciBzcGFjZVxuICAgIH1cbiAgICBpZiAodG9TY3JvbGxUb3AgPCAwKSB0b1Njcm9sbFRvcCA9IDBcblxuICAgIGlmICh0b1Njcm9sbFRvcCAhPT0gd2luU2Nyb2xsVG9wKSB7XG4gICAgICBkaWZmID0gTWF0aC5tYXgodG9TY3JvbGxUb3AsIHdpblNjcm9sbFRvcCkgLSBNYXRoLm1pbih0b1Njcm9sbFRvcCwgd2luU2Nyb2xsVG9wKVxuXG4gICAgICAvLyBDYWxjdWxhdGUgdGltZSB0byBhbmltYXRlIGJ5IHRoZSBkaWZmZXJlbmNlIGluIGRpc3RhbmNlXG4gICAgICBpZiAodHlwZW9mIHRpbWUgPT09ICd1bmRlZmluZWQnKSB0aW1lID0gZGlmZiAqIDAuMVxuICAgICAgaWYgKHRpbWUgPCAzMDApIHRpbWUgPSAzMDBcblxuICAgICAgLy8gQGRlYnVnXG4gICAgICAvLyBjb25zb2xlLmxvZygnc2Nyb2xsVG8nLCB7XG4gICAgICAvLyAgIHBvaW50OiBwb2ludCxcbiAgICAgIC8vICAgdG9TY3JvbGxUb3A6IHRvU2Nyb2xsVG9wLFxuICAgICAgLy8gICB0aW1lOiB0aW1lXG4gICAgICAvLyB9KVxuXG4gICAgICAkKCdodG1sLCBib2R5JykuYW5pbWF0ZSh7XG4gICAgICAgIHNjcm9sbFRvcDogdG9TY3JvbGxUb3AgKyAncHgnLFxuICAgICAgICBza2lwR1NBUDogdHJ1ZVxuICAgICAgfSwgdGltZSwgJ3N3aW5nJywgY2IpXG4gICAgfVxuICB9XG5cbiAgLy8gU2Nyb2xsIHRvIGFuIGl0ZW0gd2hpY2ggaGFzIGJlZW4gcmVmZXJlbmNlZCBvbiB0aGlzIHBhZ2VcbiAgJGRvYy5vbihVdGlsaXR5LmNsaWNrRXZlbnQsICdhW2hyZWZePVwiI1wiXScsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIHZhciBlbGVtSWQgPSAkKHRoaXMpLmF0dHIoJ2hyZWYnKS5yZXBsYWNlKC9eW14jXSovLCAnJylcbiAgICB2YXIgJGVsZW0gPSAkKGVsZW1JZClcbiAgICB2YXIgJHNlbGYgPSAkKHRoaXMpXG5cbiAgICAvLyBJZ25vcmUgdG9nZ2xlc1xuICAgIGlmICgkc2VsZi5ub3QoJ1tkYXRhLXRvZ2dsZV0nKS5sZW5ndGggPiAwKSB7XG4gICAgICBpZiAoJGVsZW0ubGVuZ3RoID4gMCkge1xuICAgICAgICAvLyBDdXN0b20gdG9nZ2xlc1xuICAgICAgICAvLyBAbm90ZSBtYXkgbmVlZCB0byByZWZhY3RvciB0byBwbGFjZSBsb2dpYyBpbiBiZXR0ZXIgcG9zaXRpb25cbiAgICAgICAgaWYgKCRlbGVtLmlzKCcudWktdG9nZ2xlLCBbZGF0YS10b2dnbGUtZ3JvdXBdJykpIHtcbiAgICAgICAgICAvLyBHZXQgb3RoZXIgZWxlbWVudHNcbiAgICAgICAgICAkKCdbZGF0YS10b2dnbGUtZ3JvdXA9XCInICsgJGVsZW0uYXR0cignZGF0YS10b2dnbGUtZ3JvdXAnKSArICdcIl0nKS5ub3QoJGVsZW0pLmhpZGUoKVxuICAgICAgICAgICRlbGVtLnRvZ2dsZSgpXG4gICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgICAgICAgIHJldHVyblxuICAgICAgICB9XG5cbiAgICAgICAgLy8gZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgICAgICBzY3JvbGxUbyhlbGVtSWQpXG4gICAgICB9XG4gICAgfVxuICB9KVxuXG5cblxuICAvLyBSZXNpemUgd2luZG93IChtYW51YWwgZGVib3VuY2luZyBpbnN0ZWFkIG9mIHVzaW5nIHJlcXVlc3RBbmltYXRpb25GcmFtZSlcbiAgdmFyIHRpbWVyRGVib3VuY2VSZXNpemUgPSAwXG4gICR3aW4ub24oJ3Jlc2l6ZScsIGZ1bmN0aW9uICgpIHtcbiAgICBjbGVhclRpbWVvdXQodGltZXJEZWJvdW5jZVJlc2l6ZSlcbiAgICB0aW1lckRlYm91bmNlUmVzaXplID0gc2V0VGltZW91dChmdW5jdGlvbiAoKSB7XG4gICAgICB1cGRhdGVXaW5kb3coKVxuICAgIH0sIDEwMClcbiAgfSlcblxuICAvKlxuICAgKiBTb3J0YWJsZXNcbiAgICovXG4gIC8vIFVzZXIgaW50ZXJhY3Rpb24gc29ydCBjb2x1bW5zXG4gICRkb2Mub24oVXRpbGl0eS5jbGlja0V2ZW50LCAnW2RhdGEtc29ydGFibGUtYnldJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgdmFyICR0YXJnZXQgPSAkKHRoaXMpXG4gICAgdmFyIGNvbHVtbk5hbWUgPSAkdGFyZ2V0LmF0dHIoJ2RhdGEtc29ydGFibGUtYnknKVxuXG4gICAgZXZlbnQucHJldmVudERlZmF1bHQoKVxuICAgICQodGhpcykucGFyZW50cygnW2RhdGEtc29ydGFibGVdJykudWlTb3J0YWJsZSgnc29ydCcsIGNvbHVtbk5hbWUpXG4gIH0pXG5cbiAgLypcbiAgICogQ2hhcnRzXG4gICAqL1xuICAvLyBDb252ZXJ0IGNoYXJ0IHBsYWNlaG9sZGVyIEpTT04gZGF0YSB0byBDaGFydCBEYXRhXG4gIHZhciBjaGFydEpTT04gPSB3aW5kb3cuY2hhcnRKU09OIHx8IHt9XG4gIHZhciBjaGFydERhdGEgPSB7fVxuICBmb3IgKHZhciBpIGluIGNoYXJ0SlNPTikge1xuICAgIGNoYXJ0RGF0YVtpXSA9IEpTT04ucGFyc2UoY2hhcnRKU09OW2ldKVxuICB9XG5cbiAgLy8gQnVpbGQgY2hhcnRzXG4gIGZ1bmN0aW9uIHJlbmRlckNoYXJ0cygpIHtcbiAgICAkKCdbZGF0YS1jaGFydF06dmlzaWJsZScpLm5vdCgnW2RhdGEtaGlnaGNoYXJ0cy1jaGFydF0nKS5lYWNoKGZ1bmN0aW9uIChpLCBlbGVtKSB7XG4gICAgICAvLyBHZXQgdGhlIGRhdGFcbiAgICAgIHZhciAkZWxlbSA9ICQoZWxlbSlcbiAgICAgIHZhciBjaGFydERhdGFLZXkgPSAkZWxlbS5hdHRyKCdkYXRhLWNoYXJ0JylcblxuICAgICAgLy8gSGFzIGRhdGFcbiAgICAgIGlmIChjaGFydERhdGEuaGFzT3duUHJvcGVydHkoY2hhcnREYXRhS2V5KSkge1xuICAgICAgICBjaGFydERhdGFbY2hhcnREYXRhS2V5XS5jcmVkaXRzID0ge1xuICAgICAgICAgIGVuYWJsZWQ6IGZhbHNlLFxuICAgICAgICAgIHRleHQ6ICcnXG4gICAgICAgIH1cbiAgICAgICAgJGVsZW0uaGlnaGNoYXJ0cyhjaGFydERhdGFbY2hhcnREYXRhS2V5XSlcbiAgICAgIH1cbiAgICB9KVxuICB9XG5cbiAgLy8gV2hlbiB2aWV3aW5nIGEgdGFiLCBzZWUgaWYgYW55IGNoYXJ0cyBuZWVkIHRvIGJlIHJlbmRlcmVkIGluc2lkZVxuICAkZG9jLm9uKCdzaG93bi5icy50YWInLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICByZW5kZXJDaGFydHMoKVxuXG4gICAgLy8gU2Nyb2xsIHRvIHRoZSB0YWIgaW4gdGhlIHZpZXcgdG9vXG4gICAgLy8gQG5vdGUgY3VycmVudGx5IGRpc2FibGluZyBmb3IgZnVuXG4gICAgLy8gaWYgKCQoZXZlbnQudGFyZ2V0KS5hdHRyKCdocmVmJykpIHtcbiAgICAvLyAgIHNjcm9sbFRvKCQoZXZlbnQudGFyZ2V0KS5hdHRyKCdocmVmJykpXG4gICAgLy8gfVxuICB9KVxuXG4gIC8qXG4gICAqIFByb2plY3QgU2luZ2xlXG4gICAqL1xuICAkZG9jXG4gICAgLy8gLS0gQ2xpY2sgdG8gc2hvdyBtYXBcbiAgICAub24oVXRpbGl0eS5jbGlja0V2ZW50LCAnLnVpLXByb2plY3Qtc2luZ2xlLW1hcC10b2dnbGUnLCBmdW5jdGlvbiAoZXZlbnQpIHtcbiAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KClcbiAgICAgIHRvZ2dsZVByb2plY3RTaW5nbGVNYXAoKVxuICAgIH0pXG4gICAgLy8gLS0gQW5pbWF0aW9uIEV2ZW50c1xuICAgIC5vbihVdGlsaXR5LnRyYW5zaXRpb25FbmRFdmVudCwgJy51aS1wcm9qZWN0LXNpbmdsZS1tYXAtb3BlbmluZycsIGZ1bmN0aW9uIChldmVudCkge1xuICAgICAgc2hvd1Byb2plY3RTaW5nbGVNYXAoKVxuICAgIH0pXG4gICAgLm9uKFV0aWxpdHkudHJhbnNpdGlvbkVuZEV2ZW50LCAnLnVpLXByb2plY3Qtc2luZ2xlLW1hcC1jbG9zaW5nJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgICBoaWRlUHJvamVjdFNpbmdsZU1hcCgpXG4gICAgfSlcblxuICBmdW5jdGlvbiBvcGVuUHJvamVjdFNpbmdsZU1hcCAoKSB7XG4gICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdvcGVuUHJvamVjdFNpbmdsZU1hcCcpXG4gICAgaWYgKGlzSUUoOSkgfHwgaXNJRSgnPDknKSkgcmV0dXJuIHNob3dQcm9qZWN0U2luZ2xlTWFwKClcbiAgICBpZiAoISRodG1sLmlzKCcudWktcHJvamVjdC1zaW5nbGUtbWFwLW9wZW4sIC51aS1wcm9qZWN0LXNpbmdsZS1tYXAtb3BlbmluZycpKSB7XG4gICAgICAkaHRtbC5yZW1vdmVDbGFzcygndWktcHJvamVjdC1zaW5nbGUtbWFwLW9wZW4gdWktcHJvamVjdC1zaW5nbGUtbWFwLWNsb3NpbmcnKS5hZGRDbGFzcygndWktcHJvamVjdC1zaW5nbGUtbWFwLW9wZW5pbmcnKVxuICAgIH1cbiAgfVxuXG4gIGZ1bmN0aW9uIGNsb3NlUHJvamVjdFNpbmdsZU1hcCAoKSB7XG4gICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdjbG9zZVByb2plY3RTaW5nbGVNYXAnKVxuICAgIGlmIChpc0lFKDkpIHx8IGlzSUUoJzw5JykpIHJldHVybiBoaWRlUHJvamVjdFNpbmdsZU1hcCgpXG4gICAgJGh0bWwucmVtb3ZlQ2xhc3MoJ3VpLXByb2plY3Qtc2luZ2xlLW1hcC1vcGVuaW5nIHVpLXByb2plY3Qtc2luZ2xlLW1hcC1vcGVuJykuYWRkQ2xhc3MoJ3VpLXByb2plY3Qtc2luZ2xlLW1hcC1jbG9zaW5nJylcbiAgfVxuXG4gIGZ1bmN0aW9uIHNob3dQcm9qZWN0U2luZ2xlTWFwICgpIHtcbiAgICAvLyBAZGVidWcgY29uc29sZS5sb2coJ3Nob3dQcm9qZWN0U2luZ2xlTWFwJylcbiAgICBpZiAoISRodG1sLmlzKCcudWktcHJvamVjdC1zaW5nbGUtbWFwLW9wZW4nKSkge1xuICAgICAgJGh0bWwucmVtb3ZlQ2xhc3MoJ3VpLXByb2plY3Qtc2luZ2xlLW1hcC1vcGVuaW5nIHVpLXByb2plY3Qtc2luZ2xlLW1hcC1jbG9zaW5nJykuYWRkQ2xhc3MoJ3VpLXByb2plY3Qtc2luZ2xlLW1hcC1vcGVuJylcbiAgICAgICQoJy51aS1wcm9qZWN0LXNpbmdsZS1tYXAtdG9nZ2xlIC5sYWJlbCcpLnRleHQoX18uX18oJ0hpZGUgbWFwJywgJ3Byb2plY3RTaW5nbGVNYXBIaWRlTGFiZWwnKSlcbiAgICB9XG4gIH1cblxuICBmdW5jdGlvbiBoaWRlUHJvamVjdFNpbmdsZU1hcCAoKSB7XG4gICAgLy8gQGRlYnVnIGNvbnNvbGUubG9nKCdoaWRlUHJvamVjdFNpbmdsZU1hcCcpXG4gICAgJGh0bWwucmVtb3ZlQ2xhc3MoJ3VpLXByb2plY3Qtc2luZ2xlLW1hcC1vcGVuaW5nIHVpLXByb2plY3Qtc2luZ2xlLW1hcC1vcGVuIHVpLXByb2plY3Qtc2luZ2xlLW1hcC1jbG9zaW5nJylcbiAgICAkKCcudWktcHJvamVjdC1zaW5nbGUtbWFwLXRvZ2dsZSAubGFiZWwnKS50ZXh0KF9fLl9fKCdWaWV3IG1hcCcsICdwcm9qZWN0U2luZ2xlTWFwU2hvd0xhYmVsJykpXG4gIH1cblxuICBmdW5jdGlvbiB0b2dnbGVQcm9qZWN0U2luZ2xlTWFwICgpIHtcbiAgICBpZigkaHRtbC5pcygnLnVpLXByb2plY3Qtc2luZ2xlLW1hcC1vcGVuLCAudWktcHJvamVjdC1zaW5nbGUtbWFwLW9wZW5pbmcnKSkge1xuICAgICAgY2xvc2VQcm9qZWN0U2luZ2xlTWFwKClcbiAgICB9IGVsc2Uge1xuICAgICAgb3BlblByb2plY3RTaW5nbGVNYXAoKVxuICAgIH1cbiAgfVxuXG4gIC8vIFByb2plY3QgU2luZ2xlIEluZm9cbiAgdmFyICRwcm9qZWN0U2luZ2xlSW5mb1dyYXAgPSAkKCcucHJvamVjdC1zaW5nbGUtaW5mby13cmFwJylcbiAgdmFyICRwcm9qZWN0U2luZ2xlSW5mbyA9ICQoJy5wcm9qZWN0LXNpbmdsZS1pbmZvJylcbiAgaWYgKCRwcm9qZWN0U2luZ2xlSW5mb1dyYXAubGVuZ3RoID4gMCkge1xuICAgIHdhdGNoV2luZG93LndhdGNoKCRwcm9qZWN0U2luZ2xlSW5mbywgb2Zmc2V0UHJvamVjdFNpbmdsZUluZm8pXG4gIH1cblxuICBmdW5jdGlvbiBvZmZzZXRQcm9qZWN0U2luZ2xlSW5mbyAoKSB7XG4gICAgLy8gT25seSBkbyBpZiB3aXRoaW4gdGhlIG1kL2xnIGJyZWFrcG9pbnRcbiAgICBpZiAoL21kfGxnLy50ZXN0KGN1cnJlbnRCcmVha3BvaW50KSAmJiAkcHJvamVjdFNpbmdsZUluZm8ubGVuZ3RoID4gMCkge1xuICAgICAgdmFyIHdpblNjcm9sbFRvcCA9ICR3aW4uc2Nyb2xsVG9wKClcbiAgICAgIHZhciBpbmZvVG9wID0gKCRwcm9qZWN0U2luZ2xlSW5mb1dyYXAub2Zmc2V0KCkudG9wICsgcGFyc2VGbG9hdCgkcHJvamVjdFNpbmdsZUluZm8uY3NzKCdtYXJnaW4tdG9wJykpIC0gJHNpdGVIZWFkZXIuaGVpZ2h0KCkgLSAyNSlcbiAgICAgIHZhciBtYXhJbmZvVG9wID0gJHNpdGVGb290ZXIub2Zmc2V0KCkudG9wIC0gJHdpbi5pbm5lckhlaWdodCgpIC0gMjVcbiAgICAgIHZhciB0cmFuc2xhdGVBbW91bnQgPSB3aW5TY3JvbGxUb3AgLSBpbmZvVG9wXG4gICAgICB2YXIgb2Zmc2V0SW5mbyA9IDBcblxuICAgICAgLy8gQ29uc3RyYWluIGluZm8gd2l0aGluIGNlcnRhaW4gYXJlYVxuICAgICAgaWYgKHdpblNjcm9sbFRvcCA+IGluZm9Ub3ApIHtcbiAgICAgICAgaWYgKHdpblNjcm9sbFRvcCA8IG1heEluZm9Ub3ApIHtcbiAgICAgICAgICBvZmZzZXRJbmZvID0gdHJhbnNsYXRlQW1vdW50XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgb2Zmc2V0SW5mbyA9IG1heEluZm9Ub3AgLSAzMDBcbiAgICAgICAgfVxuICAgICAgfVxuXG4gICAgICAvLyBAZGVidWdcbiAgICAgIC8vIGNvbnNvbGUubG9nKHtcbiAgICAgIC8vICAgd2luU2Nyb2xsVG9wOiB3aW5TY3JvbGxUb3AsXG4gICAgICAvLyAgIGluZm9Ub3A6IGluZm9Ub3AsXG4gICAgICAvLyAgIG1heEluZm9Ub3A6IG1heEluZm9Ub3AsXG4gICAgICAvLyAgIHRyYW5zbGF0ZUFtb3VudDogdHJhbnNsYXRlQW1vdW50LFxuICAgICAgLy8gICBvZmZzZXRJbmZvOiBvZmZzZXRJbmZvXG4gICAgICAvLyB9KVxuXG4gICAgICAkcHJvamVjdFNpbmdsZUluZm8uY3NzKHtcbiAgICAgICAgdHJhbnNmb3JtOiAndHJhbnNsYXRlWSgnICsgb2Zmc2V0SW5mbyArICdweCknXG4gICAgICB9KVxuXG4gICAgLy8gUmVzZXRcbiAgICB9IGVsc2Uge1xuICAgICAgJHByb2plY3RTaW5nbGVJbmZvLmNzcygndHJhbnNmb3JtJywgJycpXG4gICAgfVxuICB9XG5cbiAgLypcbiAgICogQ29sbGFwc2VcbiAgICovXG4gIC8vIE1hcmsgb24gW2RhdGEtdG9nZ2xlXSB0cmlnZ2VycyB0aGF0IHRoZSBjb2xsYXBzZWFibGUgaXMvaXNuJ3QgY29sbGFwc2VkXG4gICRkb2Mub24oJ3Nob3duLmJzLmNvbGxhcHNlJywgZnVuY3Rpb24gKGV2ZW50KSB7XG4gICAgdmFyIHRhcmdldFRyaWdnZXIgPSAnW2RhdGEtdG9nZ2xlPVwiY29sbGFwc2VcIl1bZGF0YS10YXJnZXQ9XCIjJyskKGV2ZW50LnRhcmdldCkuYXR0cignaWQnKSsnXCJdLFtkYXRhLXRvZ2dsZT1cImNvbGxhcHNlXCJdW2hyZWY9XCIjJyskKGV2ZW50LnRhcmdldCkuYXR0cignaWQnKSsnXCJdJ1xuICAgICQodGFyZ2V0VHJpZ2dlcikuYWRkQ2xhc3MoJ3VpLWNvbGxhcHNlLW9wZW4nKVxuICB9KVxuICAub24oJ2hpZGRlbi5icy5jb2xsYXBzZScsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIHZhciB0YXJnZXRUcmlnZ2VyID0gJ1tkYXRhLXRvZ2dsZT1cImNvbGxhcHNlXCJdW2RhdGEtdGFyZ2V0PVwiIycrJChldmVudC50YXJnZXQpLmF0dHIoJ2lkJykrJ1wiXSxbZGF0YS10b2dnbGU9XCJjb2xsYXBzZVwiXVtocmVmPVwiIycrJChldmVudC50YXJnZXQpLmF0dHIoJ2lkJykrJ1wiXSdcbiAgICAkKHRhcmdldFRyaWdnZXIpLnJlbW92ZUNsYXNzKCd1aS1jb2xsYXBzZS1vcGVuJylcbiAgfSlcblxuICAvKlxuICAgKiBEZWJ1Z1xuICAgKi9cbiAgaWYgKCQoJyNpbnZhbGlkLXJvdXRlJykubGVuZ3RoID4gMCAmJiB3aW5kb3cubG9jYXRpb24uc2VhcmNoKSB7XG4gICAgdmFyIHF1ZXJ5VmFycyA9IFtdXG5cbiAgICBpZiAoL15cXD8vLnRlc3Qod2luZG93LmxvY2F0aW9uLnNlYXJjaCkpIHtcbiAgICAgIHZhciBxdiA9ICh3aW5kb3cubG9jYXRpb24uc2VhcmNoICsgJycpLnJlcGxhY2UoJz8nLCAnJylcblxuICAgICAgLy8gU3BsaXQgYWdhaW5cbiAgICAgIGlmICgvJihhbXA7KT8vaS50ZXN0KHF2KSkge1xuICAgICAgICBxdiA9IHF2LnNwbGl0KC8mKGFtcDspPy8pXG4gICAgICB9IGVsc2Uge1xuICAgICAgICBxdiA9IFtxdl1cbiAgICAgIH1cblxuICAgICAgLy8gUHJvY2VzcyBlYWNoIHF2XG4gICAgICBmb3IgKHZhciBpID0gMDsgaSA8IHF2Lmxlbmd0aDsgaSsrKSB7XG4gICAgICAgIHZhciBxdlNwbGl0ID0gcXZbaV0uc3BsaXQoJz0nKVxuICAgICAgICBxdWVyeVZhcnNbcXZTcGxpdFswXV0gPSBxdlNwbGl0WzFdXG4gICAgICB9XG5cbiAgICAgIC8vIE91dHB1dCB0aGUgaW52YWxpZCByb3V0ZSB0byB0aGUgdmlld1xuICAgICAgJCgnI2ludmFsaWQtcm91dGUnKS5odG1sKCc8cHJlPjxjb2RlPicgKyBkZWNvZGVVUklDb21wb25lbnQocXVlcnlWYXJzLmludmFsaWRyb3V0ZSkgKyAnPC9jb2RlPjwvcHJlPicpLmNzcyh7XG4gICAgICAgIGRpc3BsYXk6ICdibG9jaydcbiAgICAgIH0pXG4gICAgfVxuICB9XG5cbiAgLypcbiAgICogRGV2ZW5pciBQcmV0ZXVyXG4gICAqL1xuICAkZG9jLm9uKCdjaGFuZ2UnLCAnaW5wdXQjZm9ybS1wcmV0ZXItYWRkcmVzcy1pcy1jb3JyZXNwb25kZW5jZScsIGZ1bmN0aW9uIChldmVudCkge1xuICAgIGNoZWNrQWRkcmVzc0lzQ29ycmVzcG9uZGVuY2UoKVxuICB9KVxuXG4gIGZ1bmN0aW9uIGNoZWNrQWRkcmVzc0lzQ29ycmVzcG9uZGVuY2UgKCkge1xuICAgIHZhciBhZGRyZXNzID0gWydzdHJlZXQnLCAnY29kZScsICd2aWxsZScsICdwYXlzJywgJ3RlbGVwaG9uZScsICdtb2JpbGUnXVxuICAgIGlmICgkKCdpbnB1dCNmb3JtLXByZXRlci1hZGRyZXNzLWlzLWNvcnJlc3BvbmRlbmNlJykuaXMoJzpjaGVja2VkJykpIHtcbiAgICAgICQoJyNmb3JtLXByZXRlci1maWVsZHNldC1jb3JyZXNwb25kZW5jZScpLmhpZGUoKVxuXG4gICAgICAvLyBDbGVhciBpbnB1dCB2YWx1ZXMgKGNoZWNrYm94IGRlZmluZXMgYWRkcmVzc2VzIGFyZSBzYW1lLCBzbyBiYWNrZW5kIHNob3VsZCByZWZlcmVuY2Ugb25seSBzaW5nbGUgYWRkcmVzcylcbiAgICAgIC8vIEBub3RlIHNob3VsZCBvbmx5IGNsZWFyIHZhbHVlcyBvbiBzdWJtaXQsIGluIGNhc2UgdXNlciBuZWVkcyB0byBlZGl0IGFnYWluIGJlZm9yZSBzdWJtaXR0aW5nXG4gICAgICAvLyBmb3IgKHZhciBpID0gMDsgaSA8IGFkZHJlc3MubGVuZ3RoOyBpKyspIHtcbiAgICAgIC8vICAgJCgnW25hbWU9XCJpZGVudGl0eVtjb3JyZXNwb25kZW5jZV1bJyArIGFkZHJlc3NbaV0gKyAnXVwiJykudmFsKCcnKVxuICAgICAgLy8gfVxuXG4gICAgfSBlbHNlIHtcbiAgICAgICQoJyNmb3JtLXByZXRlci1maWVsZHNldC1jb3JyZXNwb25kZW5jZScpLnNob3coKVxuICAgIH1cbiAgfVxuICBjaGVja0FkZHJlc3NJc0NvcnJlc3BvbmRlbmNlKClcblxuICAvKlxuICAgKiBWYWxpZGF0ZSBJQkFOIElucHV0XG4gICAqL1xuICBmdW5jdGlvbiBjaGVja0liYW5JbnB1dCAoZXZlbnQpIHtcbiAgICAvLyBEZWZhdWx0OiBjaGVjayBhbGwgb24gdGhlIHBhZ2VcbiAgICBpZiAodHlwZW9mIGV2ZW50ID09PSAndW5kZWZpbmVkJykgZXZlbnQgPSB7dGFyZ2V0OiAnLmN1c3RvbS1pbnB1dC1pYmFuIC5pYmFuLWlucHV0Jywgd2hpY2g6IDB9XG5cbiAgICAkKGV2ZW50LnRhcmdldCkuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgICAgLy8gR2V0IHRoZSBjdXJyZW50IGlucHV0XG4gICAgICB2YXIgaWJhbiA9ICQodGhpcykudmFsKCkudG9VcHBlckNhc2UoKS5yZXBsYWNlKC9bXjAtOUEtWl0rL2csICcnKVxuICAgICAgdmFyIGNhcmV0UG9zID0gJCh0aGlzKS5jYXJldCgpIHx8ICQodGhpcykudmFsKCkubGVuZ3RoXG5cbiAgICAgIC8vIFJlZm9ybWF0IHRoZSBpbnB1dCBpZiBlbnRlcmluZyB0ZXh0XG4gICAgICAvLyBAVE9ETyB3aGVuIHVzZXIgdHlwZXMgZmFzdCB0aGUgY2FyZXQgc29tZXRpbWVzIGdldHMgbGVmdCBiZWhpbmQuIE1heSBuZWVkIHRvIGZpZ3VyZSBvdXQgYmV0dGVyIG1ldGhvZCBmb3IgdGhpc1xuICAgICAgaWYgKChldmVudC53aGljaCA+PSA0OCAmJiBldmVudC53aGljaCA8PSA5MCkgfHwgKGV2ZW50LndoaWNoID49IDk2ICYmIGV2ZW50LndoaWNoIDw9IDEwNSkgfHwgZXZlbnQud2hpY2ggPT09IDggfHwgZXZlbnQud2hpY2ggPT09IDQ2IHx8IGV2ZW50LndoaWNoID09PSAzMikge1xuICAgICAgICBpZiAoaWJhbikge1xuICAgICAgICAgIC8vIEZvcm1hdCBwcmV2aWV3XG4gICAgICAgICAgdmFyIHByZXZpZXdJYmFuID0gaWJhbi5tYXRjaCgvLnsxLDR9L2cpXG4gICAgICAgICAgdmFyIG5ld0NhcmV0UG9zID0gKGNhcmV0UG9zICUgNSA9PT0gMCA/IGNhcmV0UG9zICsgMSA6IGNhcmV0UG9zKVxuXG4gICAgICAgICAgLy8gQGRlYnVnXG4gICAgICAgICAgLy8gY29uc29sZS5sb2coe1xuICAgICAgICAgIC8vICAgdmFsdWU6ICQodGhpcykudmFsKCksXG4gICAgICAgICAgLy8gICB2YWx1ZUxlbmd0aDogJCh0aGlzKS52YWwoKS5sZW5ndGgsXG4gICAgICAgICAgLy8gICBpYmFuOiBpYmFuLFxuICAgICAgICAgIC8vICAgaWJhbkxlbmd0aDogaWJhbi5sZW5ndGgsXG4gICAgICAgICAgLy8gICBncm91cENvdW50OiBwcmV2aWV3SWJhbi5sZW5ndGgsXG4gICAgICAgICAgLy8gICBncm91cENvdW50RGl2aWRlZDogcHJldmlld0liYW4ubGVuZ3RoIC8gNCxcbiAgICAgICAgICAvLyAgIGdyb3VwQ291bnRNb2Q6IHByZXZpZXdJYmFuLmxlbmd0aCAlIDQsXG4gICAgICAgICAgLy8gICBjYXJldFBvczogY2FyZXRQb3MsXG4gICAgICAgICAgLy8gICBjYXJldFBvc0RpdmlkZWQ6IGNhcmV0UG9zIC8gNCxcbiAgICAgICAgICAvLyAgIGNhcmV0UG9zTW9kOiBjYXJldFBvcyAlIDRcbiAgICAgICAgICAvLyB9KVxuXG4gICAgICAgICAgLy8gQWRkIGluIHNwYWNlcyBhbmQgYXNzaWduIHRoZSBuZXcgY2FyZXQgcG9zaXRpb25cbiAgICAgICAgICAkKHRoaXMpLnZhbChwcmV2aWV3SWJhbi5qb2luKCcgJykpLmNhcmV0KG5ld0NhcmV0UG9zKVxuICAgICAgICB9XG4gICAgICB9XG5cbiAgICAgIC8vIENoZWNrIGlmIHZhbGlkXG4gICAgICBpZiAoSWJhbi5pc1ZhbGlkKGliYW4pKSB7XG4gICAgICAgIC8vIFZhbGlkXG4gICAgICB9IGVsc2Uge1xuICAgICAgICAvLyBJbnZhbGlkXG4gICAgICB9XG4gICAgfSlcbiAgfVxuICAkZG9jLm9uKCdrZXl1cCcsICcuY3VzdG9tLWlucHV0LWliYW4gLmliYW4taW5wdXQnLCBjaGVja0liYW5JbnB1dClcbiAgY2hlY2tJYmFuSW5wdXQoKVxuXG4gIC8qXG4gICAqIFBhY2tlcnlcbiAgICovXG4gICQoJ1tkYXRhLXBhY2tlcnldJykuZWFjaChmdW5jdGlvbiAoaSwgZWxlbSkge1xuICAgIHZhciAkZWxlbSA9ICQoZWxlbSlcbiAgICB2YXIgZWxlbU9wdGlvbnMgPSBKU09OLnBhcnNlKCRlbGVtLmF0dHIoJ2RhdGEtcGFja2VyeScpIHx8ICd7fScpXG5cbiAgICAvLyBAZGVidWdcbiAgICAvLyBjb25zb2xlLmxvZygnZGF0YS1wYWNrZXJ5IG9wdGlvbnMnLCBlbGVtLCBlbGVtT3B0aW9ucylcblxuICAgICRlbGVtLnBhY2tlcnkoZWxlbU9wdGlvbnMpXG5cbiAgICAvLyBEcmFnZ2FibGUgaXRlbXNcbiAgICBpZiAoJGVsZW0uZmluZCgnLmRyYWdnYWJsZSwgW2RhdGEtZHJhZ2dhYmxlXScpLmxlbmd0aCA+IDApIHtcbiAgICAgICRlbGVtLmZpbmQoJy5kcmFnZ2FibGUsIFtkYXRhLWRyYWdnYWJsZV0nKS5lYWNoKGZ1bmN0aW9uIChqLCBpdGVtKSB7XG4gICAgICAgIHZhciBpdGVtT3B0aW9ucyA9IEpTT04ucGFyc2UoJChpdGVtKS5hdHRyKCdkYXRhLWRyYWdnYWJsZScpIHx8ICd7fScpXG5cbiAgICAgICAgLy8gU3BlY2lhbCBjYXNlXG4gICAgICAgIGlmICgkKGl0ZW0pLmlzKCcuZGFzaGJvYXJkLXBhbmVsJykpIHtcbiAgICAgICAgICBpdGVtT3B0aW9ucy5oYW5kbGUgPSAnLmRhc2hib2FyZC1wYW5lbC10aXRsZSdcbiAgICAgICAgICBpdGVtT3B0aW9ucy5jb250YWlubWVudCA9IHRydWVcbiAgICAgICAgfVxuXG4gICAgICAgIHZhciBkcmFnZ2llID0gbmV3IERyYWdnYWJpbGx5KGl0ZW0sIGl0ZW1PcHRpb25zKVxuICAgICAgICAkZWxlbS5wYWNrZXJ5KCdiaW5kRHJhZ2dhYmlsbHlFdmVudHMnLCBkcmFnZ2llKVxuICAgICAgfSlcbiAgICB9XG4gIH0pXG5cbiAgLy8gUGVyZm9ybSBvbiBpbml0aWFsaXNhdGlvblxuICBzdmc0ZXZlcnlib2R5KClcbiAgcmVuZGVyQ2hhcnRzKClcbiAgdXBkYXRlV2luZG93KClcbn0pXG4iLCJtb2R1bGUuZXhwb3J0cz17XG4gIFwiZW5cIjoge1xuICAgIFwibm9SZXN1bHRzXCI6IFwiTm8gcmVzdWx0cyBmb3VuZC4gVHJ5IGFub3RoZXIgc2VhcmNoXCJcbiAgfSxcbiAgXCJmclwiOiB7XG4gICAgXCJub1Jlc3VsdHNcIjogXCJSZXN1bHRzIG4nZXhpc3RlIHBhcy4gRXNzYXllciB1biBjaGVyY2hlIGVuY29yZVwiXG4gIH1cbn0iLCJtb2R1bGUuZXhwb3J0cz17XG4gIFwiZnJcIjoge1xuICAgIFwibnVtYmVyRGVjaW1hbFwiOiAgXCIsXCIsXG4gICAgXCJudW1iZXJNaWxsaVwiOiAgICBcIiBcIixcbiAgICBcIm51bWJlckN1cnJlbmN5XCI6IFwi4oKsXCIsXG5cbiAgICBcInRpbWVDb3VudERheXNcIjogXCJqb3Vyc1wiLFxuICAgIFwidGltZUNvdW50UmVtYWluaW5nXCI6IFwicmVzdGFudGVzXCIsXG5cbiAgICBcInNpdGVNb2JpbGVNZW51T3BlbkxhYmVsXCI6IFwiT3V2cmlyIGxlIG1lbnVcIixcbiAgICBcInNpdGVNb2JpbGVNZW51Q2xvc2VMYWJlbFwiOiBcIkZlcm1lciBsZSBtZW51XCIsXG5cbiAgICBcInNpdGVTZWFyY2hMYWJlbFwiOiBcIlJlY2hlcmNoZSBVbmlsZW5kXCIsXG4gICAgXCJzaXRlU2VhcmNoSW5wdXRQbGFjZWhvbGRlclwiOiBcIlVuZSBxdWVzdGlvbiA/XCIsXG4gICAgXCJzaXRlU2VhcmNoU3VibWl0TGFiZWxcIjogXCJMYW5jZXIgbGEgcmVjaGVyY2hlXCIsXG4gICAgXCJzaXRlU2VhcmNoU2hvd0FsbFJlc3VsdHNMYWJlbFwiOiBcIlZvaXIgdG91cyBsZXMgcsOpc3VsdGF0c1wiLFxuXG4gICAgXCJzaXRlVXNlclJlZ2lzdGVyTGFiZWxcIjogXCJJbnNjcmlwdGlvblwiLFxuICAgIFwic2l0ZVVzZXJMb2dpbkxhYmVsXCI6IFwiQ29ubmV4aW9uXCIsXG5cbiAgICBcInNpdGVGb290ZXJDb3B5cmlnaHRBbGxSaWdodHNcIjogXCJUb3VzIGRyb2l0cyByw6lzZXJ2w6lzXCIsXG5cbiAgICBcInNvY2lhbEZvbGxvd09uRmFjZWJvb2tMYWJlbFwiOiBcIlN1aXZleiBVbmlsZW5kIHN1ciBGYWNlYm9va1wiLFxuICAgIFwic29jaWFsRm9sbG93T25Ud2l0dGVyTGFiZWxcIjogXCJTdWl2ZXogVW5pbGVuZCBzdXIgVHdpdHRlclwiLFxuXG4gICAgXCJwcm9qZWN0UGVyaW9kRXhwaXJlZFwiOiBcIlByb2pldCB0ZXJtaW7DqWVcIixcblxuICAgIFwicHJvamVjdExpc3RWaWV3TGlzdExhYmVsXCI6IFwiVm9pciBsYSBsaXN0ZVwiLFxuICAgIFwicHJvamVjdExpc3RWaWV3TWFwTGFiZWxcIjogXCJWb2lyIHN1ciBsYSBjYXJ0ZVwiLFxuICAgIFwicHJvamVjdExpc3RJdGVtVHlwZVNpbmdsZVwiOiBcIlByb2pldFwiLFxuICAgIFwicHJvamVjdExpc3RJdGVtVHlwZVBsdXJhbFwiOiBcIlByb2pldHNcIixcblxuICAgIFwicHJvamVjdExpc3RGaWx0ZXJDYXRlZ29yeVwiOiBcIkNhdMOpZ29yaWVcIixcbiAgICBcInByb2plY3RMaXN0RmlsdGVyQ29zdFwiOiBcIkNvc3RcIixcbiAgICBcInByb2plY3RMaXN0RmlsdGVySW50ZXJlc3RcIjogXCJUYXV4IEludMOpcsOqdCBNb3llblwiLFxuICAgIFwicHJvamVjdExpc3RGaWx0ZXJSYXRpbmdcIjogXCJOb3RlXCIsXG4gICAgXCJwcm9qZWN0TGlzdEZpbHRlclBlcmlvZFwiOiBcIlRlbXBzIHJlc3RhbnRcIixcblxuICAgIFwicHJvamVjdExpc3RJdGVtT2ZmZXJzTGFiZWxTaW5nbGVcIjogXCJvZmZyZVwiLFxuICAgIFwicHJvamVjdExpc3RJdGVtT2ZmZXJzTGFiZWxQbHVyYWxcIjogXCJvZmZyZXNcIixcbiAgICBcInByb2plY3RMaXN0SXRlbU9mZmVyc1VzZXJTdGF0dXNJblByb2dyZXNzXCI6IFwiZW4gY291cnNcIixcbiAgICBcInByb2plY3RMaXN0SXRlbU9mZmVyc1VzZXJTdGF0dXNBY2NlcHRlZFwiOiBcImFjY2VwdMOpXCIsXG4gICAgXCJwcm9qZWN0TGlzdEl0ZW1PZmZlcnNVc2VyU3RhdHVzUmVqZWN0ZWRcIjogXCJyZWpldMOpXCIsXG4gICAgXCJwcm9qZWN0TGlzdEl0ZW1SYXRpbmdMYWJlbFwiOiBcIlJhdGVkICVkIHN1ciA1XCIsXG4gICAgXCJwcm9qZWN0TGlzdEl0ZW1QZXJpb2RMYWJlbFwiOiBcIiVkIGpvdXJzXCIsXG4gICAgXCJwcm9qZWN0TGlzdEl0ZW1QZXJpb2RFeHBpcmVkXCI6IFwiUHJvamV0IHRlcm1pbsOpZVwiLFxuICAgIFwicHJvamVjdExpc3RJdGVtVmlld0xhYmVsXCI6IFwiVm9pciBsZXMgaW5mb3JtYXRpb25zIHN1ciBjZSBwcm9qZXRcIixcblxuICAgIFwicHJvamVjdFNpbmdsZVBlcmlvZEV4cGlyZWRcIjogXCJQcm9qZXQgdGVybWluw6llXCIsXG4gICAgXCJwcm9qZWN0U2luZ2xlUmF0aW5nTGFiZWxcIjogXCJSYXRlZCAlZCBzdXIgNVwiLFxuICAgIFwicHJvamVjdFNpbmdsZU1hcFNob3dMYWJlbFwiOiBcIlZvaXIgbGEgY2FydGVcIixcbiAgICBcInByb2plY3RTaW5nbGVNYXBIaWRlTGFiZWxcIjogXCJDYWNoZXIgbGEgY2FydGVcIixcblxuICAgIFwicGFnaW5hdGlvbkl0ZW1MYWJlbFNpbmdsZVwiOiBcIlBhZ2VcIixcbiAgICBcInBhZ2luYXRpb25JdGVtTGFiZWxQbHVyYWxcIjogXCJQYWdlc1wiLFxuICAgIFwicGFnaW5hdGlvbkluZm9Mb2NhdGlvbkxhYmVsXCI6IFwiJWQgc3VyICVkXCIsXG4gICAgXCJwYWdpbmF0aW9uSW5mb05leHRMYWJlbFwiOiBcIlZvaXIgJWQgJXMgc3VpdmFudHNcIixcbiAgICBcInBhZ2luYXRpb25JbmRleFByZXZpb3VzTGFiZWxcIjogXCJMZXMgJWQgcHLDqWPDqWRlbnRzICVzXCIsXG4gICAgXCJwYWdpbmF0aW9uSW5kZXhOZXh0TGFiZWxcIjogXCIlZCAlcyBzdWl2YW50c1wiLFxuXG4gICAgXCJsaXN0U2hhcmluZ1NoYXJlVGV4dERlZmF1bHRcIjogXCJSZWdhcmRleiAlc2hhcmVUaXRsZSUgc3VyIFVuaWxlbmRcIixcbiAgICBcImxpc3RTaGFyaW5nTGlua0ZhY2Vib29rU2hhcmVMYWJlbFwiOiBcIlBhcnRhZ2VyIHN1ciBGYWNlYm9va1wiLFxuICAgIFwibGlzdFNoYXJpbmdMaW5rVHdpdHRlclNoYXJlTGFiZWxcIjogXCJQYXJ0YWdlciBzdXIgVHdpdHRlclwiLFxuXG4gICAgXCJwYWdlSG9tZVByZXRlclByb2plY3RMaXN0VGl0bGVcIjogXCIlZCBwcm9qZXRzIGVuIGNvdXJzXCIsXG5cbiAgICBcInBhZ2VQcm9qZXRzSW5kZXhIZWFkZXJUaXRsZVwiOiBcIiVzIHByb2pldHMgZW4gY291cnMsICVzIHByb2pldHMgZmluYW5jw6lzLCAlcyBwcsOqdGV1cnMgYWN0aWZzLlwiXG4gIH0sXG4gIFwiZW5cIjoge1xuICAgIFwibnVtYmVyRGVjaW1hbFwiOiAgXCIuXCIsXG4gICAgXCJudW1iZXJNaWxsaVwiOiAgICBcIixcIixcbiAgICBcIm51bWJlckN1cnJlbmN5XCI6IFwiJFwiLFxuXG4gICAgXCJ0aW1lQ291bnREYXlzXCI6IFwiZGF5c1wiLFxuICAgIFwidGltZUNvdW50UmVtYWluaW5nXCI6IFwicmVtYWluaW5nXCIsXG5cbiAgICBcInNpdGVNb2JpbGVNZW51T3BlbkxhYmVsXCI6IFwiT3BlbiBNZW51XCIsXG4gICAgXCJzaXRlTW9iaWxlTWVudUNsb3NlTGFiZWxcIjogXCJDbG9zZSBNZW51XCIsXG5cbiAgICBcInNpdGVTZWFyY2hMYWJlbFwiOiBcIlNlYXJjaCBVbmlsZW5kXCIsXG4gICAgXCJzaXRlU2VhcmNoSW5wdXRQbGFjZWhvbGRlclwiOiBcIkdvdCBhIHF1ZXN0aW9uP1wiLFxuICAgIFwic2l0ZVNlYXJjaFN1Ym1pdExhYmVsXCI6IFwiU3VibWl0IFNlYXJjaFwiLFxuICAgIFwic2l0ZVNlYXJjaFNob3dBbGxSZXN1bHRzTGFiZWxcIjogXCJWaWV3IGFsbCBzZWFyY2ggcmVzdWx0c1wiLFxuXG4gICAgXCJzaXRlVXNlclJlZ2lzdGVyTGFiZWxcIjogXCJSZWdpc3RlclwiLFxuICAgIFwic2l0ZVVzZXJMb2dpbkxhYmVsXCI6IFwiU2lnbiBpblwiLFxuXG4gICAgXCJzaXRlRm9vdGVyQ29weXJpZ2h0QWxsUmlnaHRzXCI6IFwiQWxsIFJpZ2h0cyBSZXNlcnZlZFwiLFxuXG4gICAgXCJzb2NpYWxGb2xsb3dPbkZhY2Vib29rTGFiZWxcIjogXCJGb2xsb3cgVW5pbGVuZCBvbiBGYWNlYm9va1wiLFxuICAgIFwic29jaWFsRm9sbG93T25Ud2l0dGVyTGFiZWxcIjogXCJGb2xsb3cgVW5pbGVuZCBvbiBUd2l0dGVyXCIsXG5cbiAgICBcInByb2plY3RQZXJpb2RFeHBpcmVkXCI6IFwiUHJvamVjdCBleHBpcmVkXCIsXG5cbiAgICBcInByb2plY3RMaXN0Vmlld0xpc3RMYWJlbFwiOiBcIkxpc3QgVmlld1wiLFxuICAgIFwicHJvamVjdExpc3RWaWV3TWFwTGFiZWxcIjogXCJNYXAgVmlld1wiLFxuICAgIFwicHJvamVjdExpc3RJdGVtVHlwZVNpbmdsZVwiOiBcIlByb2plY3RcIixcbiAgICBcInByb2plY3RMaXN0SXRlbVR5cGVQbHVyYWxcIjogXCJQcm9qZWN0c1wiLFxuXG4gICAgXCJwcm9qZWN0TGlzdEZpbHRlckNhdGVnb3J5XCI6IFwiQ2F0ZWdvcnlcIixcbiAgICBcInByb2plY3RMaXN0RmlsdGVyQ29zdFwiOiBcIkNvc3RcIixcbiAgICBcInByb2plY3RMaXN0RmlsdGVySW50ZXJlc3RcIjogXCJJbnRlcmVzdFwiLFxuICAgIFwicHJvamVjdExpc3RGaWx0ZXJSYXRpbmdcIjogXCJSYXRpbmdcIixcbiAgICBcInByb2plY3RMaXN0RmlsdGVyUGVyaW9kXCI6IFwiVGltZSBSZW1haW5pbmdcIixcblxuICAgIFwicHJvamVjdExpc3RJdGVtT2ZmZXJzTGFiZWxTaW5nbGVcIjogXCJvZmZlclwiLFxuICAgIFwicHJvamVjdExpc3RJdGVtT2ZmZXJzTGFiZWxQbHVyYWxcIjogXCJvZmZlcnNcIixcbiAgICBcInByb2plY3RMaXN0SXRlbU9mZmVyc1VzZXJTdGF0dXNJblByb2dyZXNzXCI6IFwiZW4gY291cnNcIixcbiAgICBcInByb2plY3RMaXN0SXRlbU9mZmVyc1VzZXJTdGF0dXNBY2NlcHRlZFwiOiBcImFjY2VwdMOpXCIsXG4gICAgXCJwcm9qZWN0TGlzdEl0ZW1PZmZlcnNVc2VyU3RhdHVzUmVqZWN0ZWRcIjogXCJyZWpldMOpXCIsXG4gICAgXCJwcm9qZWN0TGlzdEl0ZW1SYXRpbmdMYWJlbFwiOiBcIlJhdGVkICVkIG91dCBvZiA1XCIsXG4gICAgXCJwcm9qZWN0TGlzdEl0ZW1QZXJpb2RMYWJlbFwiOiBcIiVkIGRheXMgbGVmdFwiLFxuICAgIFwicHJvamVjdExpc3RJdGVtUGVyaW9kRXhwaXJlZFwiOiBcIlByb2plY3QgZXhwaXJlZFwiLFxuICAgIFwicHJvamVjdExpc3RJdGVtVmlld0xhYmVsXCI6IFwiVmlldyBtb3JlIGluZm9ybWF0aW9uIGFib3V0IHRoaXMgcHJvamVjdFwiLFxuXG4gICAgXCJwcm9qZWN0U2luZ2xlUGVyaW9kRXhwaXJlZFwiOiBcIlByb2plY3QgZXhwaXJlZFwiLFxuICAgIFwicHJvamVjdFNpbmdsZVJhdGluZ0xhYmVsXCI6IFwiUmF0ZWQgJWQgb3V0IG9mIDVcIixcbiAgICBcInByb2plY3RTaW5nbGVNYXBTaG93TGFiZWxcIjogXCJWaWV3IG1hcFwiLFxuICAgIFwicHJvamVjdFNpbmdsZU1hcEhpZGVMYWJlbFwiOiBcIkhpZGUgbWFwXCIsXG5cbiAgICBcInBhZ2luYXRpb25JdGVtTGFiZWxTaW5nbGVcIjogXCJQYWdlXCIsXG4gICAgXCJwYWdpbmF0aW9uSXRlbUxhYmVsUGx1cmFsXCI6IFwiUGFnZXNcIixcbiAgICBcInBhZ2luYXRpb25JbmZvTG9jYXRpb25MYWJlbFwiOiBcIiVkIG9mICVkXCIsXG4gICAgXCJwYWdpbmF0aW9uSW5mb05leHRMYWJlbFwiOiBcIlZpZXcgbmV4dCAlZCAlc1wiLFxuICAgIFwicGFnaW5hdGlvbkluZGV4UHJldmlvdXNMYWJlbFwiOiBcIlByZXZpb3VzICVkICVzXCIsXG4gICAgXCJwYWdpbmF0aW9uSW5kZXhOZXh0TGFiZWxcIjogXCJOZXh0ICVkICVzXCIsXG5cbiAgICBcImxpc3RTaGFyaW5nU2hhcmVUZXh0RGVmYXVsdFwiOiBcIkNoZWNrIG91dCAlc2hhcmVUaXRsZSUgb24gVW5pbGVuZFwiLFxuICAgIFwibGlzdFNoYXJpbmdMaW5rRmFjZWJvb2tTaGFyZUxhYmVsXCI6IFwiU2hhcmUgdG8gRmFjZWJvb2tcIixcbiAgICBcImxpc3RTaGFyaW5nTGlua1R3aXR0ZXJTaGFyZUxhYmVsXCI6IFwiU2hhcmUgdG8gVHdpdHRlclwiLFxuXG4gICAgXCJwYWdlSG9tZVByZXRlclByb2plY3RMaXN0VGl0bGVcIjogXCIlZCBBY3RpdmUgUHJvamVjdHNcIixcblxuICAgIFwicGFnZVByb2pldHNJbmRleEhlYWRlclRpdGxlXCI6IFwiJXMgcHJvamVjdHMgaW4gcHJvZ3Jlc3MsICVzIHByb2plY3RzIGZpbmFuY2VkLCAlcyBhY3RpdmUgbGVuZGVycy5cIlxuICB9LFxuICBcImVuLWdiXCI6IHtcbiAgICBcIm51bWJlckRlY2ltYWxcIjogIFwiLlwiLFxuICAgIFwibnVtYmVyTWlsbGlcIjogICAgXCIsXCIsXG4gICAgXCJudW1iZXJDdXJyZW5jeVwiOiBcIsKjXCJcbiAgfSxcbiAgXCJlc1wiOiB7XG4gICAgXCJudW1iZXJEZWNpbWFsXCI6ICBcIixcIixcbiAgICBcIm51bWJlck1pbGxpXCI6ICAgIFwiLlwiLFxuICAgIFwibnVtYmVyQ3VycmVuY3lcIjogXCLigqxcIlxuICB9XG59Il19
