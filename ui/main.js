//
// This app will handle the listing, additions and deletions of events.  These are associated business.
//
function ciniki_systemdocs_main() {
    //
    // events panel
    //
    this.menu = new M.panel('System Documentation', 'ciniki_systemdocs_main', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.menu');
    this.menu.data = [];
    this.menu.sections = {};
    this.menu.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            M.api.getJSONBgCb('ciniki.systemdocs.searchQuick', {'start_needle':value, 'limit':'25'}, 
                function(rsp) { 
                    M.ciniki_systemdocs_main.menu.liveSearchShow('search', null, M.gE(M.ciniki_systemdocs_main.menu.panelUID + '_' + s), rsp.results); 
                });
            return true;
        }
    };
    this.menu.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' && j == 0 ) { return d.result.package + '.' + d.result.module; }
        if( s == 'search' && j == 1 ) {
            switch (d.result.type) {
                case 'function': return d.result.name;
                case 'table': return d.result.name;
            }
        }
        return '';
    }
    this.menu.liveSearchResultCellFn = function(s, f, i, j, d) {
        if( s == 'search' && j == 0 ) { return 'M.ciniki_systemdocs_main.module.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + d.result.package + '\',\'' + d.result.module + '\');'; }
    }
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) { 
        switch (d.result.type) {
            case 'function': return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + d.result.id + '\');';
            case 'table': return 'M.ciniki_systemdocs_main.table.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + d.result.id + '\');';
        }
    };
//      this.main.liveSearchSubmitFn = function(s, search_str) {
//          M.ciniki_customers_main.searchCustomers('M.ciniki_customers_main.showMain();', search_str);
//      };
    this.menu.sectionData = function(s) { return this.sections[s].list; return this.data[s]; }
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.listValue = function(s, i, d) { return d.label; }
    this.menu.listFn = function(s, i, d) { return d.fn; }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == '_' ) {
            if( j == 0 ) { return d.package.name; }
            if( j == 1 ) { return 'Errors'; }
        }
        if( s == 'modules' ) {
            if( j == 0 ) { return d.module.package; }
            if( j == 1 ) { return d.module.name; }
        }
    };
    this.menu.rowFn = function(s, i, d) {
        if( s == '_' ) { return 'M.ciniki_systemdocs_main.errors.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + d.package.name + '\');'; }
        if( s == 'modules' ) { return 'M.ciniki_systemdocs_main.module.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + d.module.package + '\',\'' + d.module.name + '\');'; }
    };
    this.menu.open = function(cb) {
        this.data = {};
        M.api.getJSONCb('ciniki.systemdocs.packages', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_systemdocs_main.menu.openFinish(cb, rsp);
        });
    }
    this.menu.openFinish = function(cb, rsp) {
        var p = M.ciniki_systemdocs_main.menu;
        p.data['_'] = rsp.packages;
        p.data['modules'] = [];
        for(i in rsp.packages) {
            p.data.modules = p.data.modules.concat(rsp.packages[i].package.modules);
        }
        //
        // Setup the sections for the menu, based on packages returned
        //
        p.sections = {
            'search':{'label':'Search', 'type':'livesearchgrid', 'livesearchcols':2, 'hint':'', 'noData':'Nothing found',
                },
            };
        for(i in rsp.packages) {
            p.sections[rsp.packages[i].package.name] = {'label':rsp.packages[i].package.name, 'list':{
                'tables':{'label':'Tables', 'fn':'M.ciniki_systemdocs_main.table.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + rsp.packages[i].package.name + '\');'},
                'errors':{'label':'Errors', 'fn':'M.ciniki_systemdocs_main.errors.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + rsp.packages[i].package.name + '\');'},
                'modules':{'label':'Modules', 'fn':'M.ciniki_systemdocs_main.modules.open(\'M.ciniki_systemdocs_main.menu.show();\',\'' + rsp.packages[i].package.name + '\');'},
                }};
        }

//      p.sections['downloads'] = {'label':'Downloads', 'list':{
//          'downloaderrors':{'label':'Errors (XLS)', 'fn':''},
//          'downloaddocs':{'label':'Documentation (Word format)', 'fn':''},
//          }};
        p.sections['dbtools'] = {'label':'Database Tools', 'list':{
            'tablefields':{'label':'Missing table field descriptions', 'fn':'M.ciniki_systemdocs_main.toolstableblankfields.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
            'unknownfields':{'label':'Unknown Field Types', 'fn':'M.ciniki_systemdocs_main.toolstableunknownfields.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
//          'sqldbquote':{'label':'SQL non-escape variables', 'fn':'M.ciniki_systemdocs_main.showSQLNoQuote(\'M.ciniki_systemdocs_main.menu.show();\');'},
            }};
        p.sections['errtools'] = {'label':'Error Tools', 'list':{
            'duplicateerrors':{'label':'Duplicate errors', 'fn':'M.ciniki_systemdocs_main.toolsduperrors.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
            'errorgaps':{'label':'Error code gaps', 'fn':'M.ciniki_systemdocs_main.toolsgaperrors.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
            }};
        p.sections['functools'] = {'label':'Function Tools', 'list':{
            'nooverview':{'label':'Modules missing overview.txt', 'fn':'M.ciniki_systemdocs_main.toolsnooverview.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
            'checkAccess':{'label':'Improper checkAccess calls', 'fn':'M.ciniki_systemdocs_main.toolsimpropercheckaccess.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
            'missingargdesc':{'label':'Missing argument descriptions', 'fn':'M.ciniki_systemdocs_main.toolsnoargdesc.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
            'missingapikey':{'label':'Missing api_key argument', 'fn':'M.ciniki_systemdocs_main.toolsnoapikeyarg.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
            'missingreturns':{'label':'Missing return values', 'fn':'M.ciniki_systemdocs_main.toolsnoreturnvalue.open(\'M.ciniki_systemdocs_main.menu.show();\');'},
//          'errorlogs':{'label':'error_log statements', 'fn':'M.ciniki_systemdocs_main.showErrorLogs(\'M.ciniki_systemdocs_main.menu.show();\');'},
            }};
        p.refresh();
        p.show(cb);
    };
    this.menu.addButton('update', 'Update', 'M.ciniki_systemdocs_main.updateDocs();');
    this.menu.addButton('clear', 'Clear', 'M.ciniki_systemdocs_main.clearDocs();');
    this.menu.addClose('Back');

    //
    // The panel to display a list of modules for a package
    //
    this.modules = new M.panel('System Documentation', 'ciniki_systemdocs_main', 'modules', 'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.modules');
    this.modules.data = [];
    this.modules.sections = {
        'modules':{'label':'Modules', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'headerValues':['Name', 'Public'],
            'sortTypes':['text', 'text'],
            'cellClasses':['', ''],
            'noData':'No modules'
        }};
    this.modules.sectionData = function(s) { return this.data[s]; }
    this.modules.noData = function(s) { return this.sections[s].noData; }
    this.modules.cellValue = function(s, i, j, d) {
        if( s == 'modules' ) {
            if( j == 0 ) { return d.module.proper_name; }
            if( j == 1 ) { return d.module.public; }
        }
    };
    this.modules.rowFn = function(s, i, d) {
        if( s == 'modules' ) { return 'M.ciniki_systemdocs_main.module.open(\'M.ciniki_systemdocs_main.modules.show();\',\'' + d.module.package + '\',\'' + d.module.name + '\');'; }
    };
    this.modules.open = function(cb, package) {
        this.data = {};
        M.api.getJSONCb('ciniki.systemdocs.modules', {'package':package}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.modules;
            p.title = package + ' modules';
            p.data['modules'] = rsp.packages[0].package.modules;
            p.refresh();
            p.show(cb);
        });
    };
    this.modules.addClose('Back');

    //
    // The panel to display a list of tables for a package
    //
    this.tables = new M.panel('System Documentation', 'ciniki_systemdocs_main', 'tables', 'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.tables');
    this.tables.data = [];
    this.tables.sections = {
        'tables':{'label':'Tables', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'headerValues':['Table', 'Version'],
            'sortTypes':['text', 'text'],
            'cellClasses':['', ''],
            'noData':'No modules'
        }};
    this.tables.sectionData = function(s) { return this.data[s]; }
    this.tables.noData = function(s) { return this.sections[s].noData; }
    this.tables.cellValue = function(s, i, j, d) {
        if( s == 'tables' ) {
            if( j == 0 ) { return d.table.name; }
            if( j == 1 ) { return d.table.version; }
        }
    };
    this.tables.rowFn = function(s, i, d) {
        if( s == 'tables' ) { return 'M.ciniki_systemdocs_main.table.open(\'M.ciniki_systemdocs_main.tables.show();\',\'' + d.table.id + '\');'; }
    };
    this.tables.addButton('update', 'Update', 'M.ciniki_systemdocs_main.updateDocs();');
    this.tables.addClose('Back');

    //
    // The panel to display module information
    //
    this.module = new M.panel('Module', 'ciniki_systemdocs_main', 'module', 'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.module');
    this.module.data = {};
    this.module.sections = {
        'description':{'label':'Description', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No description',
            },
        'overview':{'label':'Overview', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No overview',
            },
        'notes':{'label':'Notes', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No notes',
            },
        'tables':{'label':'Tables', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No tables',
            },
        'scripts':{'label':'Scripts', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No public methods',
            },
        'public':{'label':'Public', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'headerValues':['Method', 'Publish'],
            'sortTypes':['text','text'],
            'noData':'No public methods',
            },
        'private':{'label':'Private', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No private functions',
            },
        'cron':{'label':'Cron', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No cron methods',
            },
        'web':{'label':'Web', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'noData':'No web functions',
            },
    };
    this.module.sectionData = function(s) { 
        if( s == 'overview' ) { return [this.data.overview]; }
        if( s == 'notes' ) { return [this.data.notes]; }
        if( s == 'description' ) { return [this.data.description]; }
        return this.data[s]; 
    }
    this.module.noData = function(s) { return this.sections[s].noData; }
    this.module.cellValue = function(s, i, j, d) {
        if( s == 'public' && j == 1 ) {
            return d.function.publish;
        }
        switch (s) {
            case 'overview': return this.data.overview;
            case 'notes': return this.data.notes;
            case 'description': return this.data.description;
            case 'tables': return d.table.name;
            case 'scripts': return d.function.name;
            case 'public': return d.function.package + '.' + d.function.module + '.' + d.function.file;
            case 'private': return d.function.name;
            case 'cron': return d.function.name;
            case 'web': return d.function.name;
        }
    }
    this.module.rowFn = function(s, i, d) {
        if( s == 'tables' ) { return 'M.ciniki_systemdocs_main.table.open(\'M.ciniki_systemdocs_main.module.show();\',\'' + d.table.id + '\');'; }
        if( d != null && d.function != null ) { return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.module.show();\',\'' + d.function.id + '\');'; }
    };
    this.module.open = function(cb, package, module) {
        M.api.getJSONCb('ciniki.systemdocs.module', {'package':package, 'module':module}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.module;
            p.title = package + '.' + module;
            p.data = rsp;
            if( rsp.notes != null ) {
                p.sections.notes.visible = 'yes';
            } else {
                p.sections.notes.visible = 'no';
            }
            if( rsp.description != null ) {
                p.sections.description.visible = 'yes';
            } else {
                p.sections.description.visible = 'no';
            }
            if( rsp.scripts != null ) {
                p.sections.scripts.visible = 'yes';
            } else {
                p.sections.scripts.visible = 'no';
            }
            if( rsp.cron != null ) {
                p.sections.cron.visible = 'yes';
            } else {
                p.sections.cron.visible = 'no';
            }
            if( rsp.web != null ) {
                p.sections.web.visible = 'yes';
            } else {
                p.sections.web.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    };
    this.module.addClose('Back');

    //
    // Panel to display table information
    //
    this.table = new M.panel('Table', 'ciniki_systemdocs_main', 'table', 'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.table');
    this.table.data = {};
    this.table.sections = {
        'description':{'label':'Description', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':[''],
            'noData':'No description',
            },
        'fields':{'label':'Fields', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['multiline aligntop', 'multiline aligntop'],
            },
        'create_sql':{'label':'SQL', 'type':'configtext', 'num_cols':1,
            'headerValues':null,
            'noData':'No SQL',
            },
    };
    this.table.sectionData = function(s) { return this.data[s]; }
    this.table.noData = function(s) { return this.sections[s].noData; }
    this.table.cellValue = function(s, i, j, d) {
        if( s == 'fields' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.field.name + '</span><span class="subtext">' + d.field.type + '</span>';
                case 1: return d.field.description;
            }
        }
        switch (s) {
            case 'description': return this.data.description;
            case 'create_sql': return this.data.create_sql;
        }
    }
    this.table.open = function(cb, tid) {
        this.table_id = tid;
        M.api.getJSONCb('ciniki.systemdocs.table', {'table_id':tid}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.table;
            p.title = rsp.table.name;
            p.data = {
                'description':[rsp.table.description],
                'create_sql':[rsp.table.create_sql],
                'fields':rsp.table.fields,
                };
            p.refresh();
            p.show(cb);
        });
    };
    this.table.open = function(cb, package) {
        M.api.getJSONCb('ciniki.systemdocs.tables', {'package':package}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.tables;
            p.title = package + ' tables';
            p.data.tables = rsp.packages[0].package.tables;
            p.refresh();
            p.show(cb);
        });
    };
    this.table.addButton('update', 'Update', 'M.ciniki_systemdocs_main.updateDocsCb(\'M.ciniki_systemdocs_main.table.open(null,M.ciniki_systemdocs_main.table.table_id);\');');
    this.table.addClose('Back');

    //
    // Panel to display function information
    //
    this.function = new M.panel('Function', 'ciniki_systemdocs_main', 'function', 'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.function');
    this.function.cbStacked = 'yes';
    this.function.data = {};
    this.function.sections = {
        'description':{'label':'Description', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':[''],
            'noData':'No description',
            },
        'returns':{'label':'Returns', 'visible':'no', 'type':'configtext'},
        'args':{'label':'Arguments', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['multiline aligntop', 'multiline aligntop'],
            'noData':'No arguments',
            },
        'calls':{'label':'Calls', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No calls',
            },
        'errors':{'label':'Errors', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['', 'multiline'],
            'noData':'No errors',
            },
        'extended_errors':{'label':'Extended Errors', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['', 'multiline'],
            'noData':'No extended errors',
            },
    };
    this.function.sectionData = function(s) { return this.data[s]; }
    this.function.noData = function(s) { return this.sections[s].noData; }
    this.function.cellValue = function(s, i, j, d) {
        if( s == 'args' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.argument.name + '</span><span class="subtext">' + d.argument.options + '</span>';
                case 1: return d.argument.description;
            }
        }
        if( s == 'calls' ) {
            switch (j) {
                case 0: return d.function.call;
            }
        }
        if( s == 'errors' ) {
            switch (j) {
                case 0: return d.error.code;
                case 1: return '<span class="maintext">' + d.error.msg + '</span><span class="subtext">' + d.error.pmsg + '</span>';
            }
        }
        if( s == 'extended_errors' ) {
            switch (j) {
                case 0: return d.error.code;
                case 1: return '<span class="maintext">' + d.error.name + '</span><span class="subtext">' + d.error.msg + '</span>';
            }
        }
        switch (s) {
            case 'description': return this.data.description;
            case 'returns': return this.data.returns;
        }
    }
    this.function.rowFn = function(s, i, d) {
        if( s == 'calls' ) { return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.function.open(null,' + this.function_id + ');\',\'' + d.function.id + '\');'; }
        if( s == 'extended_errors' ) { return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.function.open(null,' + this.function_id + ');\',\'' + d.error.function_id + '\');'; }
        return null;
    };
    this.function.open = function(cb, fid) {
        this.function_id = fid;
        M.api.getJSONCb('ciniki.systemdocs.function', {'function_id':this.function_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.function;
            if( rsp.function.type == 'scripts' ) {
                p.title = rsp.function.name;
                p.sections.returns.visible = 'no';
                p.sections.args.visible = 'no';
            } else if( rsp.function.type == 'public' ) {
                p.title = rsp.function.package + '.' + rsp.function.module + '.' + rsp.function.file;
                p.sections.returns.visible = 'yes';
                p.sections.args.visible = 'yes';
            } else {
                p.title = rsp.function.name;
                p.sections.returns.visible = 'no';
                p.sections.args.visible = 'yes';
            }
            p.data = {
                'description':[rsp.function.description],
                'returns':[rsp.function.returns],
                'args':rsp.function.args,
                'calls':rsp.function.calls,
                'errors':rsp.function.errors,
                'extended_errors':rsp.function.extended_errors,
                };
            p.refresh();
            p.show(cb);
        });
    };
    this.function.addButton('update', 'Update', 'M.ciniki_systemdocs_main.updateDocsCb(\'M.ciniki_systemdocs_main.function.open(null,M.ciniki_systemdocs_main.function.function_id);\');');
    this.function.addClose('Back');

    //
    // Panel to display list of errors
    //
    this.errors = new M.panel('Errors', 'ciniki_systemdocs_main', 'errors', 'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.errors');
    this.errors.data = {};
    this.errors.sections = {
        'errors':{'label':'', 'type':'simplegrid', 'num_cols':2, 'sortable':'yes',
            'headerValues':['Code', 'Msg'],
            'sortTypes':['number', ''],
            'cellClasses':['', 'multiline'],
            'noData':'No errors',
            },
    };
    this.errors.sectionData = function(s) { return this.data[s]; }
    this.errors.noData = function(s) { return this.sections[s].noData; }
    this.errors.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.error.code;
            case 1: return '<span class="maintext">' + d.error.msg + '</span><span class="subtext">' + d.error.pmsg + '</span>';
        }
    };
    this.errors.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.errors.show();\',\'' + d.error.function_id + '\');';
    };
    this.errors.open = function(cb, package) {
        M.api.getJSONCb('ciniki.systemdocs.errors', {'package':package}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.errors;
            p.title = package + ' errors';
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.errors.addClose('Back');

    //
    // Panel to display missing table field descriptions
    //
    this.toolstableblankfields = new M.panel('Missing Table Field Description',
        'ciniki_systemdocs_main', 'toolstableblankfields',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolstableblankfields');
    this.toolstableblankfields.sections = {
        'tables':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No missing table field descriptions',
            },
    };
    this.toolstableblankfields.sectionData = function(s) { return this.data[s]; }
    this.toolstableblankfields.noData = function(s) { return this.sections[s].noData; }
    this.toolstableblankfields.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.table.name;
        }
    };
    this.toolstableblankfields.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.table.open(\'M.ciniki_systemdocs_main.toolstableblankfields.open();\',\'' + d.table.id + '\');';
    };
    this.toolstableblankfields.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsTableBlankFields', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolstableblankfields;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.toolstableblankfields.addButton('update', 'Update', 'M.ciniki_systemdocs_main.updateDocsCb(\'M.ciniki_systemdocs_main.toolstableblankfields.show();\');');
    this.toolstableblankfields.addClose('Back');

    //
    // Panel to display missing table field types
    //
    this.toolstableunknownfields = new M.panel('Unknown Field Types',
        'ciniki_systemdocs_main', 'toolstableunknownfields',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolstableunknownfields');
    this.toolstableunknownfields.sections = {
        'tables':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No unknown field types',
            },
    };
    this.toolstableunknownfields.sectionData = function(s) { return this.data[s]; }
    this.toolstableunknownfields.noData = function(s) { return this.sections[s].noData; }
    this.toolstableunknownfields.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.table.name;
        }
    };
    this.toolstableunknownfields.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.table.open(\'M.ciniki_systemdocs_main.toolstableunknownfields.show();\',\'' + d.table.id + '\');';
    };
    this.toolstableunknownfields.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsTableUnknownFields', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolstableunknownfields;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.toolstableunknownfields.addClose('Back');

    //
    // Panel to display duplicate errors
    //
    this.toolsduperrors = new M.panel('Duplicate Errors',
        'ciniki_systemdocs_main', 'toolsduperrors',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolsduperrors');
    this.toolsduperrors.sections = {
        'errors':{'label':'Duplicates', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['multiline', 'multiline'],
            'noData':'No duplicate errors',
            },
    };
    this.toolsduperrors.sectionData = function(s) { return this.data[s]; }
    this.toolsduperrors.noData = function(s) { return this.sections[s].noData; }
    this.toolsduperrors.cellValue = function(s, i, j, d) {
        if( s == 'errors' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.error.code + '</span><span class="subtext">' + d.error.package + '</span>';
                case 1: return '<span class="maintext">' + d.error.package + '-api/' + d.error.module + '/' + d.error.type + '/' + d.error.file + '</span><span class="subtext">' + d.error.msg + '</span>';
            }
        } else {
            switch (j) {
                case 0: return d.error.package;
                case 1: return d.error.code;
            }
        }
    };
    this.toolsduperrors.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.toolsduperrors.show();\',\'' + d.error.function_id + '\');';
    };
    this.toolsduperrors.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsDupErrors', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolsduperrors;
            p.data = {'errors':rsp.errors};
            M.api.getJSONCb('ciniki.systemdocs.toolsGapErrors', {}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                for(i in rsp.packages) {
                    p.sections[rsp.packages[i].package.name] = {'label':rsp.packages[i].package.name + ' gaps, last code: ' + rsp.lastcodes[rsp.packages[i].package.name] + ']', 
                        'type':'simplegrid', 'num_cols':2,
                        'headerValues':null,
                        'cellClasses':['', ''],
                        'noData':'No gaps',
                    };
                    p.data[rsp.packages[i].package.name] = rsp.packages[i].package.gaps;
                };
                p.refresh();
                p.show(cb);
            });
        });
    };
    this.toolsduperrors.addButton('update', 'Update', 'M.ciniki_systemdocs_main.updateDocsCb(\'M.ciniki_systemdocs_main.toolsduperrors.open();\');');
    this.toolsduperrors.addClose('Back');

    //
    // Panel to display error gaps 
    //
    this.toolsgaperrors = new M.panel('Gap Errors',
        'ciniki_systemdocs_main', 'toolsgaperrors',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolsgaperrors');
    this.toolsgaperrors.sections = {};
    this.toolsgaperrors.sectionData = function(s) { return this.data[s]; }
    this.toolsgaperrors.noData = function(s) { return this.sections[s].noData; }
    this.toolsgaperrors.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.error.package;
            case 1: return d.error.code;
        }
    };
    this.toolsgaperrors.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsGapErrors', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolsgaperrors;
            p.sections = {};
            p.data = [];
            for(i in rsp.packages) {
                p.sections[rsp.packages[i].package.name] = {'label':rsp.packages[i].package.name, 
                    'type':'simplegrid', 'num_cols':2,
                    'headerValues':null,
                    'cellClasses':['', ''],
                    'noData':'No gaps',
                };
                p.data[rsp.packages[i].package.name] = rsp.packages[i].package.gaps;
            };
            p.refresh();
            p.show(cb);
        });
    };
    this.toolsgaperrors.addClose('Back');

    //
    // Panel to display module missing overview.txt
    //
    this.toolsnooverview = new M.panel('Modules Missing overview.txt',
        'ciniki_systemdocs_main', 'toolsnooverview',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolsnooverview');
    this.toolsnooverview.sections = {
        'modules':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No modules missing overview.txt',
            },
    };
    this.toolsnooverview.sectionData = function(s) { return this.data[s]; }
    this.toolsnooverview.noData = function(s) { return this.sections[s].noData; }
    this.toolsnooverview.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.module.package + '.' + d.module.name;
        }
    };
    this.toolsnooverview.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.module.open(\'M.ciniki_systemdocs_main.toolsnooverview.show();\',\'' + d.module.package + '\',\'' + d.module.name + '\');';
    };
    this.toolsnooverview.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsNoOverview', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolsnooverview;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.toolsnooverview.addClose('Back');

    //
    // Panel to display improper calls to checkAccess
    //
    this.toolsimpropercheckaccess = new M.panel('Improper checkAccess Calls',
        'ciniki_systemdocs_main', 'toolsimpropercheckaccess',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolsimpropercheckaccess');
    this.toolsimpropercheckaccess.sections = {
        'functions':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No improper check access calls',
            },
    };
    this.toolsimpropercheckaccess.sectionData = function(s) { return this.data[s]; }
    this.toolsimpropercheckaccess.noData = function(s) { return this.sections[s].noData; }
    this.toolsimpropercheckaccess.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.function.package + '.' + d.function.module + '.' + d.function.file;
        }
    };
    this.toolsimpropercheckaccess.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.toolsimpropercheckaccess.show();\',\'' + d.function.id + '\');';
    };
    this.toolsimpropercheckaccess.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsImproperCheckAccess', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolsimpropercheckaccess;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.toolsimpropercheckaccess.addClose('Back');

    //
    // Panel to display functions with missing argument descriptions
    //
    this.toolsnoargdesc = new M.panel('No argument descriptions',
        'ciniki_systemdocs_main', 'toolsnoargdesc',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolsnoargdesc');
    this.toolsnoargdesc.sections = {
        'functions':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No missing argument descriptions',
            },
    };
    this.toolsnoargdesc.sectionData = function(s) { return this.data[s]; }
    this.toolsnoargdesc.noData = function(s) { return this.sections[s].noData; }
    this.toolsnoargdesc.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.function.package + '-api/' + d.function.module + '/' + d.function.type + '/' + d.function.file;
        }
    };
    this.toolsnoargdesc.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.toolsnoargdesc.show();\',\'' + d.function.id + '\');';
    };
    this.toolsnoargdesc.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsNoArgDesc', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolsnoargdesc;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.toolsnoargdesc.addClose('Back');

    //
    // Panel to display functions with missing api_key or auth_token arguments
    //
    this.toolsnoapikeyarg = new M.panel('No api_key args',
        'ciniki_systemdocs_main', 'toolsnoapikeyarg',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolsnoapikeyarg');
    this.toolsnoapikeyarg.sections = {
        'functions':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No functions missing api_key',
            },
    };
    this.toolsnoapikeyarg.sectionData = function(s) { return this.data[s]; }
    this.toolsnoapikeyarg.noData = function(s) { return this.sections[s].noData; }
    this.toolsnoapikeyarg.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.function.package + '-api/' + d.function.module + '/' + d.function.type + '/' + d.function.file;
        }
    };
    this.toolsnoapikeyarg.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.toolsnoapikeyarg.show();\',\'' + d.function.id + '\');';
    };
    this.toolsnoapikeyarg.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsNoAPIKeyArg', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolsnoapikeyarg;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.toolsnoapikeyarg.addClose('Back');

    //
    // Panel to display functions missing return values
    //
    this.toolsnoreturnvalue = new M.panel('No return values',
        'ciniki_systemdocs_main', 'toolsnoreturnvalue',
        'mc', 'medium', 'sectioned', 'ciniki.systemdocs.main.toolsnoreturnvalue');
    this.toolsnoreturnvalue.sections = {
        'functions':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['', ''],
            'noData':'No missing return values',
            },
    };
    this.toolsnoreturnvalue.sectionData = function(s) { return this.data[s]; }
    this.toolsnoreturnvalue.noData = function(s) { return this.sections[s].noData; }
    this.toolsnoreturnvalue.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.function.package + '-api/' + d.function.module + '/' + d.function.type + '/' + d.function.file;
        }
    };
    this.toolsnoreturnvalue.rowFn = function(s, i, d) {
        return 'M.ciniki_systemdocs_main.function.open(\'M.ciniki_systemdocs_main.toolsnoreturnvalue.show();\',\'' + d.function.id + '\');';
    };
    this.toolsnoreturnvalue.open = function(cb) {
        M.api.getJSONCb('ciniki.systemdocs.toolsNoReturnValue', {}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_systemdocs_main.toolsnoreturnvalue;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.toolsnoreturnvalue.addClose('Back');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_systemdocs_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }

    //
    // Global functions
    //
    this.updateDocs = function() {
        M.api.getJSONCb('ciniki.systemdocs.update', {}, function() {M.ciniki_systemdocs_main.menu.open();});
    };

    this.updateDocsCb = function(cb) {
        M.ciniki_systemdocs_main.update_docs_cb = cb;
        M.api.getJSONCb('ciniki.systemdocs.update', {}, function() {eval(M.ciniki_systemdocs_main.update_docs_cb);});
    };

    this.clearDocs = function() {
        M.api.getJSONCb('ciniki.systemdocs.clear', {}, function() {M.ciniki_systemdocs_main.menu.open();});
    };
}
