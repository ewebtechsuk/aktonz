( function () {
	"use strict";

	const eventHub = new Vue();

	let jetAbafSettingsPage = {
		methods: {
			saveSettings: function () {
				let self = this;

				jQuery.ajax( {
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_abaf_save_settings',
						settings: self.settings,
						nonce: window?.JetABAFConfig?.nonce
					},
				} ).done( function ( response ) {
					if ( response.success ) {
						if ( response.data.reload ) {
							setTimeout( function () {
								window.location.reload();
							}, 3000 );
						}

						self.$CXNotice.add( {
							message: response.data.message,
							type: 'success',
							duration: 7000,
						} );
					} else {
						self.$CXNotice.add( {
							message: response.data.message,
							type: 'error',
							duration: 7000,
						} );
					}
				} ).fail( function ( _, _2, errorThrown ) {
					self.$CXNotice.add( {
						message: errorThrown,
						type: 'error',
						duration: 15000,
					} );
				} );
			}
		}
	};

	Vue.component( 'jet-abaf-settings-general', {
		template: '#jet-abaf-settings-general',
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		data: function() {
			return {
				postTypes: window.JetABAFConfig.post_types,
				generalSettings: {}
			};
		},
		mounted: function() {
			this.generalSettings = this.settings;
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-labels', {
		template: '#jet-abaf-settings-labels',
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		data: function() {
			return {
				advancedSettings: {}
			};
		},
		mounted: function() {
			this.advancedSettings = this.settings;
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-field-settings', {
		template: '#jet-abaf-settings-field-settings',
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		data: function() {
			return {
				fieldsSettings: {}
			};
		},
		mounted: function() {
			this.fieldsSettings = this.settings;
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-advanced', {
		template: '#jet-abaf-settings-advanced',
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		data: function() {
			return {
				advancedSettings: {},
				cronSchedules: window.JetABAFConfig.cron_schedules,
			};
		},
		mounted: function() {
			this.advancedSettings = this.settings;
		},
		methods: {
			getInterval: function( to ) {
				const res = [];

				for ( let i = 0; i <= to; i++ ) {
					let item = {};
					let val  = '';

					if ( 10 > i ) {
						val = '' + '0' + i;
					} else {
						val = i;
					}

					item.value = val;
					item.label = val;

					res.push( item );
				}

				return res;
			},
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-configuration', {
		template: '#jet-abaf-settings-configuration',
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		data: function() {
			return {
				configurationSettings: {}
			};
		},
		mounted: function() {
			this.configurationSettings = this.settings;

			this.$nextTick( function () {
				eventHub.$on( 'update-settings', this.updateSetting );
			} );
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-common-config', {
		template: '#jet-abaf-settings-common-config',
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$nextTick( function() {
					eventHub.$emit( 'update-settings', value, key );
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-schedule', {
		template: '#jet-abaf-settings-schedule',
		mixins: [ jetAbafSettingsPage, scheduleManager, dateMethods ],
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		data() {
			return {
				timeSettings: true
			};
		},
		methods: {
			getTimeSettings: function( key ) {
				const dateObject = moment.duration( parseInt( this.settings[ key ] ), 'seconds' );
				const minutes = dateObject._data.minutes < 10 ? `0${dateObject._data.minutes}` : dateObject._data.minutes;
				const hours = dateObject._data.hours < 10 ? `0${dateObject._data.hours}` : dateObject._data.hours;

				return `${hours}:${minutes}`;
			},
			onUpdateTimeSettings: function( valueObject ) {
				const timestamp = moment.duration( valueObject.value ).asSeconds();
				this.updateSetting( timestamp, valueObject.key );
			},
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-macros-inserter', {
		template: '#jet-abaf-settings-macros-inserter',
		directives: { clickOutside: window.JetVueUIClickOutside },
		data() {
			return {
				macrosList: window.JetABAFConfig.macros_list,
				isActive: false,
				currentMacros: {},
				editMacros: false,
				result: {}
			};
		},
		methods: {
			applyMacros( macros, force ) {
				force = force || false;

				if ( macros ) {
					this.$set( this.result, 'macros', macros.id );
					this.$set( this.result, 'macrosName', macros.name );

					if ( macros.controls ) {
						this.$set( this.result, 'macrosControls', macros.controls );
					}
				}

				if ( macros && ! force && macros.controls ) {
					this.currentMacros = macros;
					this.editMacros = true;

					return;
				}

				this.$emit( 'input', this.formatResult() );

				this.isActive = false;
				this.result = {};

				this.resetEdit();
			},
			checkCondition( condition ) {
				let checkResult = true;

				condition = condition || {};

				for ( const [ fieldName, check ] of Object.entries( condition ) ) {
					if ( check && check.length ) {
						if ( ! check.includes( this.result[ fieldName ] ) ) {
							checkResult = false;
						}
					} else {
						if ( check !== this.result[ fieldName ] ) {
							checkResult = false;
						}
					}
				}

				return checkResult;
			},
			formatResult() {
				if ( ! this.result.macros ) {
					return;
				}

				let res = '%';

				res += this.result.macros;

				if ( this.result.macrosControls ) {
					for ( const prop in this.result.macrosControls ) {
						res += '|';

						if ( undefined !== this.result[ prop ] ) {
							res += this.result[ prop ];
						}
					}
				}

				res += '%';

				if ( this.result.advancedSettings && ( this.result.advancedSettings.fallback || this.result.advancedSettings.context ) ) {
					res += JSON.stringify( this.result.advancedSettings );
				}

				return res;
			},
			getControlValue( control ) {
				if ( this.result[ control.name ] ) {
					return this.result[ control.name ];
				} else if ( control.default ) {
					this.setMacrosArg( control.default, control.name );

					return control.default;
				}
			},
			getPreparedControls() {
				let controls = [];

				for ( const controlID in this.currentMacros.controls ) {
					let control = this.currentMacros.controls[ controlID ];
					let optionsList = [];
					let type = control.type;
					let label = control.label;
					let defaultVal = control.default;
					let groupsList = [];
					let condition = control.condition || {};

					switch ( control.type ) {
						case 'text':
							type = 'cx-vui-input';
							break;

						case 'select':
							type = 'cx-vui-select';

							if ( control.groups ) {
								for ( let i = 0; i < control.groups.length; i++ ) {
									let group = control.groups[ i ];
									let groupOptions = [];

									for ( const optionValue in group.options ) {
										groupOptions.push( {
											value: optionValue,
											label: group.options[ optionValue ],
										} );
									}

									groupsList.push( {
										label: group.label,
										options: groupOptions,
									} );

								}
							} else {
								for ( const optionValue in control.options ) {
									optionsList.push( {
										value: optionValue,
										label: control.options[ optionValue ],
									} );
								}
							}

							break;

						default:
							break;
					}

					controls.push( {
						type: type,
						name: controlID,
						label: label,
						default: defaultVal,
						optionsList: optionsList,
						groupsList: groupsList,
						condition: condition,
						value: control.default,
					} );
				}

				return controls;
			},
			onClickOutside() {
				this.isActive = false;
				this.resetEdit();
			},
			resetEdit() {
				this.currentMacros = {};
				this.editMacros = false;
			},
			setMacrosArg( value, arg ) {
				this.$set( this.result, arg, value );
			},
			switchIsActive() {
				this.isActive = ! this.isActive;

				if ( this.isActive ) {
					if ( this.result.macros ) {
						for (let i = 0; i < this.macrosList.length; i++ ) {
							if ( this.result.macros === this.macrosList[ i ].id && this.macrosList[ i ].controls ) {
								this.currentMacros = this.macrosList[ i ];
								this.editMacros = true;
							}
						}
					}
				} else {
					this.resetEdit();
				}
			},
		}
	} );

	Vue.component( 'jet-abaf-settings-workflow-item', {
		template: '#jet-abaf-settings-workflow-item',
		props: {
			value: {
				type: Object,
				default: {},
			},
		},
		data() {
			return {
				item: {},
				confirmDelete: false,
				events: window.JetABAFConfig.events,
				actions: window.JetABAFConfig.actions,
			};
		},
		created() {
			this.item = { ...this.value }

			if ( this.item.actions?.length ) {
				for ( let i = 0; i < this.item.actions.length; i++ ) {
					this.$set( this.item.actions[ i ], 'collapsed', true );
				}
			}
		},
		methods: {
			deleteItem() {
				this.$emit( 'delete', this.item );
			},
			updateItem( value, key ) {
				this.$set( this.item, key, value );

				this.$nextTick( () => {
					this.$emit( 'input', this.item );
				} );
			},

			// Actions methods.
			addAction() {
				this.item.actions.push( {
					action_id: 'send-email',
					collapsed: false,
				} );
			},
			updateActions( actions ) {
				this.item.actions = [ ...actions ];
				this.$emit( 'input', this.item );
			},
			cloneAction( data, index ) {
				const action = { ...this.item.actions[ index ] };
				this.$set( this.item.actions, this.item.actions.length, action );
			},
			deleteAction( data, index ) {
				this.item.actions.splice( index, 1 );
			},
			addActionMacros( index, prop, value ) {
				let currentVal = this.item.actions[ index ][ prop ];

				if ( currentVal ) {
					let controlEl = this.$refs[ prop ][0].$el.querySelector( '.cx-vui-textarea, .cx-vui-input' );

					if ( ! controlEl ) {
						currentVal = currentVal + ' ' + value;
					} else {
						let startPos = controlEl.selectionStart;
						let endPos   = controlEl.selectionEnd;

						if ( 0 <= startPos ) {
							currentVal = currentVal.substring( 0, startPos ) + value + currentVal.substring( endPos, currentVal.length );
						} else {
							currentVal = currentVal + ' ' + value;
						}
					}
				} else {
					currentVal = value;
				}

				this.setActionProp( index, prop, currentVal );
			},
			setActionProp( index, prop, value ) {
				this.$set( this.item.actions[ index ], prop, value );
			},
			getActionTitle( action ) {
				if ( action.title ) {
					return action.title;
				} else {
					for ( let i = 0; i < this.actions.length; i++ ) {
						if ( action.action_id === this.actions[ i ].value ) {
							return this.actions[ i ].label;
						}
					}

					return action.action_id;
				}
			},
			isCollapsed( object ) {
				return undefined === object.collapsed || true === object.collapsed;
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-workflows', {
		template: '#jet-abaf-settings-workflows',
		props: {
			settings: {
				type: Object,
				default: {},
			}
		},
		data() {
			return {
				workflows: window.JetABAFConfig.workflows,
				debounceTimeout: null
			};
		},
		watch: {
			workflows: {
				handler( list ) {
					if ( this.debounceTimeout ) {
						clearTimeout( this.debounceTimeout );
					}

					this.debounceTimeout = setTimeout( () => {
						wp.apiFetch( {
							method: 'POST',
							path: window.JetABAFConfig.api.update_workflows,
							data: {
								workflows: list
							},
						} ).then( () => {
							this.$CXNotice.add( {
								message: 'Workflows saved!',
								type: 'success',
								duration: 7000
							} );
						} );

						this.debounceTimeout = null;
					}, 300 );
				},
				deep: true,
			}
		},
		methods: {
			deleteWorkflowItem( index ) {
				this.workflows.splice( index, 1 );
			},
			newWorkflowItem() {
				this.workflows.push( {
					event: '',
					schedule: 'immediately',
					actions: [],
					hash: Math.round( Math.random() * ( 999999 - 100000 ) + 100000 ),
				} );
			},
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	Vue.component( 'jet-abaf-settings-tools', {
		template: '#jet-abaf-settings-tools',
		props: {
			settings: {
				type: Object,
				default: {},
			},
			dbTablesExists: {
				type: Boolean
			}
		},
		data: function() {
			return {
				toolsSettings: {},
				processingTables: false
			};
		},
		mounted: function() {
			this.toolsSettings = this.settings;
		},
		methods: {
			processTables: function() {
				let self = this;

				self.processingTables = true;

				jQuery.ajax( {
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_abaf_process_tables',
						nonce: window?.JetABAFConfig?.nonce
					},
				} ).done( function( response ) {
					self.processingTables = false;

					if ( response.success ) {
						if ( ! self.dbTablesExists ) {
							self.dbTablesExists = true;
						}

						self.$CXNotice.add( {
							message: response.data.message,
							type: 'success',
							duration: 7000,
						} );
					} else {
						self.$CXNotice.add( {
							message: response.data.message,
							type: 'error',
							duration: 15000,
						} );
					}
				} ).fail( function( _, _2, errorThrown ) {
					self.processingTables = false;

					self.$CXNotice.add( {
						message: errorThrown,
						type: 'error',
						duration: 15000,
					} );
				} );

			},
			addNewColumn: function() {
				this.toolsSettings.additional_columns.push( {
					column: '',
					collapsed: false,
				} );
			},
			setColumnProp: function( index, key, value ) {
				let col = this.toolsSettings.additional_columns[ index ];

				col[ key ] = value;

				this.toolsSettings.additional_columns.splice( index, 1, col );
				this.updateSetting( this.toolsSettings.additional_columns, 'additional_columns' );
			},
			cloneColumn: function( index ) {
				let col    = this.toolsSettings.additional_columns[ index ],
					newCol = {
						'column': col.column + '_copy',
					};

				this.toolsSettings.additional_columns.splice( index + 1, 0, newCol );
				this.updateSetting( this.toolsSettings.additional_columns, 'additional_columns' );
			},
			deleteColumn: function( index ) {
				this.toolsSettings.additional_columns.splice( index, 1 );
				this.updateSetting( this.toolsSettings.additional_columns, 'additional_columns' );
			},
			isCollapsed: function( object ) {
				return undefined === object.collapsed || true === object.collapsed;
			},
			updateSetting: function( value, key ) {
				this.$emit( 'force-update', {
					key: key,
					value: value,
				} );
			}
		}
	} );

	new Vue( {
		el: '#jet-abaf-settings-page',
		template: '#jet-abaf-settings',
		mixins: [ jetAbafSettingsPage ],
		data: {
			dbTablesExists: !! window.JetABAFConfig.db_tables_exists,
			settings: window.JetABAFConfig.settings,
		},
		computed: {
			initialTab: function() {
				let result = 'general';

				if ( ! this.dbTablesExists ) {
					result = 'tools';
				}

				return result;
			},
		},
		methods: {
			onUpdateSettings: function( setting, force ) {
				force = force || false;

				this.$set( this.settings, setting.key, setting.value );

				if ( force ) {
					this.$nextTick( function() {
						this.saveSettings();
					} );
				}
			}
		}
	} );
} ) ();
