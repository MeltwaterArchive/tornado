var JCSDLDefinition = (function() {
    'use strict';

    // define some privates
    var

    /**
     * Name of this definition object (for easy differentiation between instances)
     *
     * @type {String}
     */
    name = 'facebook',

    /**
     * List of all possible targets and their fields and their types.
     *
     * @type {Object}
     */
    targets = {
        // general interaction
        interaction : {
            name : 'All',
            fields : {
                content : {name: 'Content', preset: 'string'},
                hashtags : {name: 'Hashtags', preset: 'string'},
                media_type : {name: 'Media Type', preset: 'multiSelect', options: {'link':'Link','photo_album':'Photo Album','note':'Note','photo':'Photo','post':'Post','reshare':'Reshare','video':'Video'}},
                raw_content : {name: 'Raw Content', preset: 'string'},
                subtype : {name: 'Subtype', preset: 'string'}
            }
        },

        // facebook
        fb : {
            name : 'Facebook',
            fields : {
                author : {
                    name : 'Author',
                    fields : {
                        age : {name: 'Age', preset: 'multiSelect', options: {'18-24':'18-24','25-34':'25-34','35-44':'35-44','45-54':'45-54','55-64':'55-64','65+':'65+','unknown':'unknown'}},
                        country : {name: 'Country', preset: 'string'},
                        country_code : {name: 'Country Code', preset: 'multiSelect', optionsSet: 'country'},
                        gender : {name: 'Gender', preset: 'multiSelect', options: {'female':'Female','male':'Male','unknown':'Unknown'}},
                        region : {name: 'Region', preset: 'string'},
                        type : {name: 'Type', icon: 'type-alt', preset: 'multiSelect', options: {'page':'Page','user':'User'}}
                    }
                },
                content : {name: 'Content', preset: 'string'},
                hashtags : {name: 'Hashtags', preset: 'string'},
                language : {name: 'Language', preset: 'multiSelect', optionsSet: 'language'},
                sentiment : {name: 'Sentiment', preset: 'sliderRange', min: -1, max: 1, 'default': 0},
                link : {name: 'Link', preset: 'url'},
                media_type : {name: 'Media Type', preset: 'multiSelect', options: {'link':'Link','photo_album':'Photo Album','note':'Note','photo':'Photo','post':'Post','reshare':'Reshare','video':'Video'}},
                parent : {
                    name : 'Parent',
                    icon : 'fb_parent',
                    fields : {
                        author : null,
                        content : null,
                        hashtags : null,
                        link : null,
                        media_type : null,
                        language : null,
                        sentiment : null,
                        topic_ids : {name: 'Topic IDs', icon: 'topics_ids', preset: 'stringNumber'},
                        topics : null
                    }
                },
                topic_ids : {name: 'Topic IDs', icon: 'topics_ids', preset: 'int'},
                topics : {
                    name : 'Topics',
                    fields : {
                        name : {name: 'Name', preset: 'string'},
                        about : {name: 'About', preset: 'string'},
                        company_overview : {name: 'Company Overview', preset: 'string'},
                        location_address : {name: 'Address', icon: 'address', preset: 'string'},
                        location_city : {name: 'City', icon: 'city', preset: 'string'},
                        mission : {name: 'Mission', preset: 'string'},
                        products : {name: 'Products', preset: 'string'},
                        category : {name: 'Category', preset: 'string'},
                        release_date : {name: 'Release Date', preset: 'string'},
                        username : {name: 'Username', preset: 'string'},
                        website : {name: 'Website', preset: 'string'},
                    }
                },
                type : {name: 'Type', preset: 'multiSelect', options: {'story':'Story','comment':'Comment','like':'Like','reshare':'Reshare'}}
            }
        },

        links : {
            name : 'Links',
            fields : {
                code : {name: 'Code', icon: 'http_code', preset: 'stringNumber'},
                domain : {name: 'Domain', preset: 'string'},
                normalized_url : {name: 'Normalized URL', preset: 'url'},
                url : {name: 'URL', preset: 'url'}
            }
        }

    },

    /**
     * Target configuration presets.
     *
     * @type {Object}
     */
    presets = {
        'string' : {
            type: 'string',
            cs: true,
            input: 'text',
            operators: ['exists', 'equals', 'substr', 'contains_any', 'all', 'wildcard', 'contains_near', 'different', 'regex_partial', 'regex_exact', 'in'],
            operator: 'contains_any'
        },
        'url' : {
            type: 'string',
            input: 'text',
            operators: ['exists', 'equals', 'substr', 'url_in', 'contains_any', 'all', 'wildcard', 'contains_near', 'different', 'regex_partial', 'regex_exact'],
            operator: 'url_in'
        },
        'singleSelect' : {
            type: 'string',
            input: 'select',
            single: true,
            operators: ['exists', 'equals', 'different']
        },
        'multiSelect' : {
            type: 'string',
            input: 'select',
            operators: ['exists', 'equals', 'different', 'in'],
            operator: 'in'
        },
        'geo' : {
            type: 'geo',
            input: ['geo_box', 'geo_radius', 'geo_polygon'],
            operators: ['exists', 'geo_box', 'geo_radius', 'geo_polygon']
        },
        'int' : {
            type: 'int',
            input: 'number',
            operators: ['exists', 'equals', 'different', 'greaterThan', 'lowerThan']
        },
        'intArray' : {
            type: 'int',
            input: 'number',
            operators: ['exists', 'equals', 'different', 'in'],
            operator: 'in'
        },
        'stringNumber' : {
            type: 'string',
            input: 'text',
            operators: ['exists', 'equals', 'different', 'in']
        },
        'sliderRange' : {
            type: 'int',
            input: 'slider',
            operators: ['greaterThan', 'lowerThan']
        },
        'sliderRangeEquals' : {
            type: 'int',
            input : 'slider',
            operators: ['equals', 'greaterThan', 'lowerThan']
        }
    },

    /**
     * Definition of CSDL operators.
     *
     * @type {Object}
     */
    operators = {
        substr : {
            label : 'Substring',
            description : 'Filter for a sequence of characters that form a word or part of a word.',
            code : 'substr',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=substr'
        },
        all : {
            label : 'Contains All',
            description : 'Filter for all strings in the list of strings.',
            code : 'contains_all',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=contains_all'
        },
        contains_any : {
            label : 'Contains words',
            description : 'Filter for one or more string values from a list of strings.',
            code : 'contains_any',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=contains_any'
        },
        contains_near : {
            label : 'Contains words near',
            description : 'Filter for two or more words that occur near to each other.',
            code : 'contains_near',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=contains_near'
        },
        wildcard : {
            label : 'Wildcard',
            description : 'Filter for strings using a wildcard character *.',
            code : 'wildcard',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=wildcard'
        },
        different : {
            label : 'Different',
            description : 'Not equal to...',
            code : '!=',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=equals-and-not-equals'
        },
        equals : {
            label : 'Equals',
            description : 'Equal to...',
            code : '==',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=equals-and-not-equals'
        },
        'in' : {
            label : 'In',
            description : 'Filter for one or more values from a list.',
            code : 'in',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=in'
        },
        url_in : {
            label : 'URL In',
            description : 'Filter for an exact match with a normalized URL.',
            code : 'url_in',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=url_in'
        },
        greaterThan : {
            label : '&gt;=',
            description : 'Greater than...',
            code : '>='
        },
        lowerThan : {
            label : '&lt;=',
            description : 'Lower than...',
            code : '<=',
        },
        exists : {
            label : 'Exists',
            description : 'Check whether a target is present.',
            code : 'exists',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=exists'
        },
        regex_partial : {
            label : 'Partial Regex',
            description : 'Filter for content by a regular expression that matches any part of the target.',
            code : 'regex_partial',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=regex_partial'
        },
        regex_exact : {
            label : 'Exact Regex',
            description : 'Filter for content by a regular expression that matches the entire target.',
            code : 'regex_exact',
            jsonp : 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id=regex_exact'
        },
        geo_box : {
            label : 'Geo Box',
            description : 'Filter for content originating from geographical locations within a bounding box.',
            code : 'geo_box'
        },
        geo_radius : {
            label : 'Geo Radius',
            description : 'Filter for posts originating inside a circle.',
            code : 'geo_radius'
        },
        geo_polygon : {
            label : 'Geo Polygon',
            description : 'Filter for content originating from geographical locations defined by a polygon with up to 32 vertices.',
            code : 'geo_polygon'
        }
    },

    /**
     * URL pattern for target help API.
     *
     * @type {String}
     */
    targetHelpJsonpSource = 'https://dev.datasift.com/tooltip-endpoint/tooltip/retrieve?callback=jcsdlJSONP&id={target}',

    /**
     * Definition of JCSDL VQB input types.
     *
     * @type {Object}
     */
    inputs = {
        text : {
            // list of operators for which the input field is a "tag" input field
            arrayOperators : ['contains_any', 'all', 'contains_near', 'all', 'in'],
            operator : 'contains_any'
        },
        number : {
            arrayOperators : ['in'],
            operator : 'equals'
        },
        select : {
            operator : 'in',
            sets : {
                language : {'af': 'Afrikaans', 'ar': 'Arabic', 'bg': 'Bulgarian', 'zh': 'Chinese', 'cs': 'Czech', 'da': 'Danish', 'nl': 'Dutch', 'en': 'English', 'et': 'Estonian', 'fi': 'Finnish', 'fr': 'French', 'de': 'German', 'el': 'Greek', 'he': 'Hebrew', 'hu': 'Hungarian', 'is': 'Icelandic', 'it': 'Italian', 'ja': 'Japanese', 'ko': 'Korean', 'la': 'Latin', 'lt': 'Lithuanian', 'lv': 'Latvian', 'no': 'Norwegian', 'pl': 'Polish', 'pt': 'Portuguese', 'ro': 'Romanian', 'ru': 'Russian', 'es': 'Spanish', 'sv': 'Swedish', 'tl': 'Tagalog', 'tr': 'Turkish'},
                country : {"AF":"Afghanistan","AX":"Aland Islands !Åland Islands","AL":"Albania","DZ":"Algeria","AS":"American Samoa","AD":"Andorra","AO":"Angola","AI":"Anguilla","AQ":"Antarctica","AG":"Antigua and Barbuda","AR":"Argentina","AM":"Armenia","AW":"Aruba","AU":"Australia","AT":"Austria","AZ":"Azerbaijan","BS":"Bahamas","BH":"Bahrain","BD":"Bangladesh","BB":"Barbados","BY":"Belarus","BE":"Belgium","BZ":"Belize","BJ":"Benin","BM":"Bermuda","BT":"Bhutan","BO":"Bolivia, Plurinational State of","BQ":"Bonaire, Sint Eustatius and Saba","BA":"Bosnia and Herzegovina","BW":"Botswana","BV":"Bouvet Island","BR":"Brazil","IO":"British Indian Ocean Territory","BN":"Brunei Darussalam","BG":"Bulgaria","BF":"Burkina Faso","BI":"Burundi","KH":"Cambodia","CM":"Cameroon","CA":"Canada","CV":"Cabo Verde","KY":"Cayman Islands","CF":"Central African Republic","TD":"Chad","CL":"Chile","CN":"China","CX":"Christmas Island","CC":"Cocos (Keeling) Islands","CO":"Colombia","KM":"Comoros","CG":"Congo","CD":"Congo, the Democratic Republic of the","CK":"Cook Islands","CR":"Costa Rica","CI":"Cote d'Ivoire !Côte d'Ivoire","HR":"Croatia","CU":"Cuba","CW":"Curacao !Curaçao","CY":"Cyprus","CZ":"Czech Republic","DK":"Denmark","DJ":"Djibouti","DM":"Dominica","DO":"Dominican Republic","EC":"Ecuador","EG":"Egypt","SV":"El Salvador","GQ":"Equatorial Guinea","ER":"Eritrea","EE":"Estonia","ET":"Ethiopia","FK":"Falkland Islands (Malvinas)","FO":"Faroe Islands","FJ":"Fiji","FI":"Finland","FR":"France","GF":"French Guiana","PF":"French Polynesia","TF":"French Southern Territories","GA":"Gabon","GM":"Gambia","GE":"Georgia","DE":"Germany","GH":"Ghana","GI":"Gibraltar","GR":"Greece","GL":"Greenland","GD":"Grenada","GP":"Guadeloupe","GU":"Guam","GT":"Guatemala","GG":"Guernsey","GN":"Guinea","GW":"Guinea-Bissau","GY":"Guyana","HT":"Haiti","HM":"Heard Island and McDonald Islands","VA":"Holy See (Vatican City State)","HN":"Honduras","HK":"Hong Kong","HU":"Hungary","IS":"Iceland","IN":"India","ID":"Indonesia","IR":"Iran, Islamic Republic of","IQ":"Iraq","IE":"Ireland","IM":"Isle of Man","IL":"Israel","IT":"Italy","JM":"Jamaica","JP":"Japan","JE":"Jersey","JO":"Jordan","KZ":"Kazakhstan","KE":"Kenya","KI":"Kiribati","KP":"Korea, Democratic People's Republic of","KR":"Korea, Republic of","KW":"Kuwait","KG":"Kyrgyzstan","LA":"Lao People's Democratic Republic","LV":"Latvia","LB":"Lebanon","LS":"Lesotho","LR":"Liberia","LY":"Libya","LI":"Liechtenstein","LT":"Lithuania","LU":"Luxembourg","MO":"Macao","MK":"Macedonia, the former Yugoslav Republic of","MG":"Madagascar","MW":"Malawi","MY":"Malaysia","MV":"Maldives","ML":"Mali","MT":"Malta","MH":"Marshall Islands","MQ":"Martinique","MR":"Mauritania","MU":"Mauritius","YT":"Mayotte","MX":"Mexico","FM":"Micronesia, Federated States of","MD":"Moldova, Republic of","MC":"Monaco","MN":"Mongolia","ME":"Montenegro","MS":"Montserrat","MA":"Morocco","MZ":"Mozambique","MM":"Myanmar","NA":"Namibia","NR":"Nauru","NP":"Nepal","NL":"Netherlands","NC":"New Caledonia","NZ":"New Zealand","NI":"Nicaragua","NE":"Niger","NG":"Nigeria","NU":"Niue","NF":"Norfolk Island","MP":"Northern Mariana Islands","NO":"Norway","OM":"Oman","PK":"Pakistan","PW":"Palau","PS":"Palestine, State of","PA":"Panama","PG":"Papua New Guinea","PY":"Paraguay","PE":"Peru","PH":"Philippines","PN":"Pitcairn","PL":"Poland","PT":"Portugal","PR":"Puerto Rico","QA":"Qatar","RE":"Reunion !Réunion","RO":"Romania","RU":"Russian Federation","RW":"Rwanda","BL":"Saint Barthelemy !Saint Barthélemy","SH":"Saint Helena, Ascension and Tristan da Cunha","KN":"Saint Kitts and Nevis","LC":"Saint Lucia","MF":"Saint Martin (French part)","PM":"Saint Pierre and Miquelon","VC":"Saint Vincent and the Grenadines","WS":"Samoa","SM":"San Marino","ST":"Sao Tome and Principe","SA":"Saudi Arabia","SN":"Senegal","RS":"Serbia","SC":"Seychelles","SL":"Sierra Leone","SG":"Singapore","SX":"Sint Maarten (Dutch part)","SK":"Slovakia","SI":"Slovenia","SB":"Solomon Islands","SO":"Somalia","ZA":"South Africa","GS":"South Georgia and the South Sandwich Islands","SS":"South Sudan","ES":"Spain","LK":"Sri Lanka","SD":"Sudan","SR":"Suriname","SJ":"Svalbard and Jan Mayen","SZ":"Swaziland","SE":"Sweden","CH":"Switzerland","SY":"Syrian Arab Republic","TW":"Taiwan, Province of China","TJ":"Tajikistan","TZ":"Tanzania, United Republic of","TH":"Thailand","TL":"Timor-Leste","TG":"Togo","TK":"Tokelau","TO":"Tonga","TT":"Trinidad and Tobago","TN":"Tunisia","TR":"Turkey","TM":"Turkmenistan","TC":"Turks and Caicos Islands","TV":"Tuvalu","UG":"Uganda","UA":"Ukraine","AE":"United Arab Emirates","GB":"United Kingdom","US":"United States","UM":"United States Minor Outlying Islands","UY":"Uruguay","UZ":"Uzbekistan","VU":"Vanuatu","VE":"Venezuela, Bolivarian Republic of","VN":"Viet Nam","VG":"Virgin Islands, British","VI":"Virgin Islands, U.S.","WF":"Wallis and Futuna","EH":"Western Sahara","YE":"Yemen","ZM":"Zambia","ZW":"Zimbabwe"},
                salienceTopics : {'Advertising':'Advertising','Agriculture':'Agriculture','Art':'Art','Automotive':'Automotive','Aviation':'Aviation','Banking':'Banking','Beverages':'Beverages','Biotechnology':'Biotechnology','Business':'Business','Crime':'Crime','Disasters':'Disasters','Economics':'Economics','Education':'Education','Elections':'Elections','Energy':'Energy','Fashion':'Fashion','Food':'Food','Hardware':'Hardware','Health':'Health','Hotels':'Hotels','Intellectual Property':'Intellectual Property','Investing':'Investing','Labor':'Labor','Law':'Law','Marriage':'Marriage','Mobile Devices':'Mobile Devices','Politics':'Politics','Real Estate':'Real Estate','Renewable Energy':'Renewable Energy','Robotics':'Robotics','Science':'Science','Social Media':'Social Media','Software and Internet':'Software and Internet','Space':'Space','Sports':'Sports','Technology':'Technology','Traditional':'Traditional','Travel':'Travel','Video Games':'Video Games','War':'War','Weather':'Weather'},
                newscredCategories : {'Africa':'Africa', 'Asia':'Asia', 'Business':'Business', 'Entertainment':'Entertainment', 'Environment':'Environment', 'Europe':'Europe', 'Health':'Health', 'Lifestyle':'Lifestyle', 'Other':'Other', 'Politics':'Politics', 'Regional':'Regional', 'Sports':'Sports', 'Technology':'Technology', 'Travel':'Travel', 'U.K.':'U.K.', 'U.S.':'U.S.', 'World':'World'}
            }
        },
        slider : {
            operator : 'greaterThan',
            min : 0,
            max : 100,
            step : 1,
            'default': 50,
            displayFormat : function(v) {return v;}
        },
        geo : {

        },
        geo_box : {
            operators : ['geo_box'],
            instructions : [
                'Click on the map to mark first corner of the box.',
                'Now click on the map to mark the second corner of the box.',
                'You can drag the markers around to change the box coordinates.'
            ]
        },
        geo_radius : {
            operators : ['geo_radius'],
            instructions : [
                'Click on the map to mark the center of the selection.',
                'Click again to set the radius.',
                'You can drag the markers around to move the center of the circle or the radius.'
            ]
        },
        geo_polygon : {
            operators : ['geo_polygon'],
            instructions : [
                'Click on the map to mark first tip of the polygon selection.',
                'Click on the map to mark the second tip of the polygon.',
                'Click on the map to mark the third tip and close the shape.',
                'Click on the map to add new markers or drag them around. Double-click a marker to remove it.'
            ]
        }
    },

    /**
     * The final definition object that is returned.
     *
     * @type {Object}
     */
    definition = {
        name : name,
        targets : {},
        operators : operators,
        targetHelpJsonpSource : targetHelpJsonpSource,
        inputs : inputs
    },

    /**
     * Merge two or more objects together.
     *
     * The rightmost argument has the top priority.
     *
     * @return {Object}
     */
    merge = function() {
        var result = {};

        for (var i in arguments) {
            if (arguments.hasOwnProperty(i)) {
                if (typeof arguments[i] !== 'object') {
                    continue;
                }

                for (var key in arguments[i]) {
                    if (arguments[i].hasOwnProperty(key)) {
                        result[key] = arguments[i][key];
                    }
                }
            }
        }

        return result;
    },

    /**
     * Applies presets defined on target and its subfields and returns it.
     *
     * @param  {Object} target A target definition.
     * @return {Object}
     */
    parseTargetDefinition = function(target) {
        // some targets are null as they should copy from other targets (see bottom of the file)
        if (target === null) {
            return target;
        }

        // if target has subfields then iterate over them and parse them
        if (target.hasOwnProperty('fields')) {
            for (var name in target.fields) {
                if (target.fields.hasOwnProperty(name)) {
                    target.fields[name] = parseTargetDefinition(target.fields[name]);
                }
            }

        // if its a "real" target then apply a preset on it
        } else {
            // but only if it has a preset of course
            if (target.hasOwnProperty('preset')) {
                if (!presets.hasOwnProperty(target.preset)) {
                    throw new Error('Undefined preset "' + target.preset + '" used.');
                }

                target = merge(presets[target.preset], target);
            }
        }

        return target;
    };

    // go through all targets and apply presets on them
    for (var source in targets) {
        if (targets.hasOwnProperty(source)) {
            definition.targets[source] = parseTargetDefinition(targets[source]);
        }
    }

    // and finally copy some targets to others, as they're identical
    definition.targets.fb.fields.parent.fields.author = definition.targets.fb.fields.author;
    definition.targets.fb.fields.parent.fields.content = definition.targets.fb.fields.content;
    definition.targets.fb.fields.parent.fields.hashtags = definition.targets.fb.fields.hashtags;
    definition.targets.fb.fields.parent.fields.sentiment = definition.targets.fb.fields.sentiment;
    definition.targets.fb.fields.parent.fields.link = definition.targets.fb.fields.link;
    definition.targets.fb.fields.parent.fields.media_type = definition.targets.fb.fields.media_type;
    definition.targets.fb.fields.parent.fields.language = definition.targets.fb.fields.language;
    definition.targets.fb.fields.parent.fields.topics = definition.targets.fb.fields.topics;

    return definition;

})();