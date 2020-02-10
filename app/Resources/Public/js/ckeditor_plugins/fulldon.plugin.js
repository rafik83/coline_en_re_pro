CKEDITOR.plugins.add('strinsert',
    {
        requires : ['richcombo'],
        init : function( editor )
        {
            //  array of strings to choose from that'll be inserted into the editor
            var strings = [];
            strings.push(['{{nom_entreprise}}', 'Nom de l\'entreprise', 'Ajouter le nom de l\'entreprise si ça existe ']);
            strings.push(['{{nom_donateur}}', 'Nom du donateur', 'Ajouter le nom du donateur']);
            strings.push(['{{prenom_donateur}}', 'Prénom du donateur', 'Ajouter le prénom du donateur']);
            strings.push(['{{civilite_donateur}}', 'Civilité du donateur', 'Ajouter la civilité du donateur']);
            strings.push(['{{donateur_id}}', 'Identifiant du donateur', 'Ajouter l\'dentifiant du donateur']);
            strings.push(['{{adresse_donateur}}', 'Adresse du donateur', 'Ajouter l\'adresse du donateur']);
            strings.push(['{{date_envoi}}', 'Date de l\'envoi ', 'Ajouter la date d\'envoi']);
            strings.push(['{{ref_don}}', 'Référence du don', 'Ajouter la référence du don']);
            strings.push(['{{montant_don}}', 'Montant du don', 'Ajouter le montant du don']);
            strings.push(['{{code_activite}}', 'Code d\'activité du don', 'Ajouter le code activité du don']);
            strings.push(['{{pa_history}}', 'Historique des PA', 'Ajouter l\'historique des PA']);
            strings.push(['{{pa_somme}}', 'Somme des PA', 'Ajouter la somme des PA']);
            strings.push(['{{recu_id}}', 'Référence du RF', 'Ajouter la Référence du Reçu fiscal']);
            strings.push(['{{rum}}', 'RUM du PA', 'Ajouter le RUM du PA']);
            strings.push(['{{mode_don}}', 'Mode du don', 'Ajouter le mode de versement du don']);
            strings.push(['{{date_fiscale}}', 'Date fiscale', 'Ajouter la date fiscale du don']);
            strings.push(['{{annee_fiscale}}', 'Année fiscale', 'Ajouter l\'année fiscale du don']);


            // add the menu to the editor
            editor.ui.addRichCombo('strinsert',
                {
                    label: 		'RF Fulldon',
                    title: 		'RF Fulldon',
                    voiceLabel: 'RF Fulldon',
                    className: 	'cke_format',
                    multiSelect:false,
                    panel:
                    {
                        css: [ editor.config.contentsCss, CKEDITOR.skin.getPath('editor') ],
                        voiceLabel: editor.lang.panelVoiceLabel
                    },

                    init: function()
                    {
                        this.startGroup( "RF Fulldon" );
                        for (var i in strings)
                        {
                            this.add(strings[i][0], strings[i][1], strings[i][2]);
                        }
                    },

                    onClick: function( value )
                    {
                        editor.focus();
                        editor.fire( 'saveSnapshot' );
                        editor.insertHtml(value);
                        editor.fire( 'saveSnapshot' );
                    }
                });
        }
    });

CKEDITOR.plugins.add('emarketinginsert',
    {
        requires : ['richcombo'],
        init : function( editor )
        {
            //  array of strings to choose from that'll be inserted into the editor
            var strings = [];
            strings.push(['[[nom_donateur]]', 'Nom du donateur', 'Ajouter le nom du donateur']);
            strings.push(['[[prenom_donateur]]', 'Prénom du donateur', 'Ajouter le prénom du donateur']);
            strings.push(['[[civilite_donateur]]', 'Civilité du donateur', 'Ajouter la civilité du donateur']);
            strings.push(['[[donateur_id]]', 'Identifiant du donateur', 'Ajouter l\'dentifiant du donateur']);
            strings.push(['[[adresse_donateur]]', 'Adresse du donateur', 'Ajouter l\'adresse du donateur']);
            strings.push(['[[date_envoi]]', 'Date de l\'envoi ', 'Ajouter la date d\'envoi']);
            strings.push(['[[ref_don]]', 'Référence du don', 'Ajouter la référence du don']);
            strings.push(['[[montant_don]]', 'Montant du don', 'Ajouter le montant du don']);
            strings.push(['[[code_activite]]', 'Code d\'activité du don', 'Ajouter le code activité du don']);
            strings.push(['[[pa_history]]', 'Historique des PA', 'Ajouter l\'historique des PA']);
            strings.push(['[[pa_somme]]', 'Somme des PA', 'Ajouter la somme des PA']);
            strings.push(['[[recu_id]]', 'Référence du RF', 'Ajouter la Référence du Reçu fiscal']);
            strings.push(['[[rum]]', 'RUM du PA', 'Ajouter le RUM du PA']);
            strings.push(['[[date_prelevement]]', 'Date de Prélevement', 'Ajouter la date du prélevement courant']);
            strings.push(['[[periodicite]]', 'Périodicité', 'Ajouter la péridicité du don PA']);
            strings.push(['[[unsub_link]]', 'Lien de désabonnement', 'Ajouter un lien de désabonnement']);
            strings.push(['[[viewhtml_link]]', 'Lien vers l\'email en ligne', 'Ajouter un lien pour visualiser l\'email en ligne']);

            // add the menu to the editor
            editor.ui.addRichCombo('emarketinginsert',
                {
                    label: 		'Emarketing',
                    title: 		'Emarketing',
                    voiceLabel: 'Emarketing',
                    className: 	'cke_format',
                    multiSelect:false,
                    panel:
                    {
                        css: [ editor.config.contentsCss, CKEDITOR.skin.getPath('editor') ],
                        voiceLabel: editor.lang.panelVoiceLabel
                    },

                    init: function()
                    {
                        this.startGroup( "Emarketing Fulldon" );
                        for (var i in strings)
                        {
                            this.add(strings[i][0], strings[i][1], strings[i][2]);
                        }
                    },

                    onClick: function( value )
                    {
                        editor.focus();
                        editor.fire( 'saveSnapshot' );
                        editor.insertHtml(value);
                        editor.fire( 'saveSnapshot' );
                    }
                });
        }
    });