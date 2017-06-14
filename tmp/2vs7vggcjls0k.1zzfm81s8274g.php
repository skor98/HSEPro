    <link href="<?php echo $BASE; ?>/ui/css/plugins/summernote/summernote.css" rel="stylesheet">
    <link href="<?php echo $BASE; ?>/ui/css/plugins/summernote/summernote-bs3.css" rel="stylesheet">
    <link href="<?php echo $BASE; ?>/ui/css/plugins/jasny/jasny-bootstrap.min.css" rel="stylesheet">

    <style>
     
      mark {
        background: #efefef;
        font-weight: bold;
      }

    </style>
  </head>
  <body>
    <div>
      <form class="row" data-bind="submit: reload">
        <p class="col-md-10">
          <input type="search" class="form-control" data-bind="value: q" placeholder="Введите слово или фразу для поиска" />
        </p>
        <p class="col-md-2">
          <button class="ladda-button btn btn-primary btn-block"  data-style="zoom-in" data-bind="click: reload">Поиск</button>
        </p>
      </form>

      <ul class="ibox" data-bind="foreach: hits" style="list-style-type: none; margin-top: 11px;">
        <li style="margin-top: 14px;">
          <div class="ibox-heading ibox-title" style="background-color: white">
            <a data-bind="attr: { href: _source.url}"><strong data-bind="text: _source.filename"></strong></a>                       
          </div>

          <div class="ibox-title">
            <?php if (in_array(200, $permissions)): ?>
              <span class="label label-success pull-left" data-bind="text: _source.student_name" style="padding: 7px 11px;"></span>
              <span class="label label-primary pull-left" data-bind="text: _source.subject" style="padding: 7px 11px;"></span>
              <span class="label label-danger pull-left" data-bind="text: _source.program" style="padding: 7px 11px;"></span>
              <span class="label label-warning pull-left" data-bind="text: _source.year" style="padding: 7px 11px;"></span>
            <?php endif; ?>

            <?php if (in_array(100, $permissions)): ?>
              <span class="label label-success pull-left" data-bind="text: _source.teacher_name" style="padding: 7px 11px;"></span>
              <span class="label label-primary pull-left" data-bind="text: _source.subject" style="padding: 7px 11px;"></span>
            <?php endif; ?>
          </div>

          <div data-bind="if: hasHighlights" class="ibox-content">
            <ol data-bind="foreach: highlight_text">
              <li style="padding-left: 10px;" data-bind="html: $data"></li>
            </ol>
          </div>
        </li>
      </ul>
    </div>

    <script src="<?php echo $BASE; ?>/ui/js/jquery-2.1.1.js"></script>
    <script src="<?php echo $BASE; ?>/ui/js/bootstrap.js"></script>
    <script src="<?php echo $BASE; ?>/ui/js/plugins/knockout/dist/knockout.debug.js"></script>
    <script src="<?php echo $BASE; ?>/ui/js/plugins/elasticsearch/elasticsearch.js"></script>
    <script>
      function Hit(data) {

        var self = this;

        ko.utils.extend(self, data);

        self.highlight_text = self.highlight["content.content"];
        self.hasHighlights = self.highlight && self.highlight["content.content"] && self.highlight["content.content"].length > 0;

      }

      function Model() {
        var self = this;

        self.index = 'homeworks';
        self.type = 'docs'; 

        self.client = new elasticsearch.Client({
          host: 'https://elastic:xkzw2PQEqHHYZszlKSpQPYne@99e05075fa8fd2eadf169fa6bd09cf34.eu-west-1.aws.found.io:9243'
        });

        self.q = ko.observable('');
        self.hits = ko.observableArray();

        self.reload = function() {
          var body = {
            index: self.index,
            type: self.type,
            body: {
              _source: {}
            }
          };

         

          if (<?php echo $id_role['id_role']; ?> == 1) {
          var body2 = {
           index: self.index,
           type: self.type,
           body: {
            query: {
              bool: {
                must: [
                {
                  query_string: {
                    query: self.q()
                  }
                },
                {
                  term: { "student_id" : <?php echo $id_user; ?> }
                }
                ]
                
              } 
              

            },
            highlight: {
              require_field_match: false,
              fields: {
                "*": {
                  "pre_tags": [
                  "<mark>"
                  ],
                  "post_tags": [
                  "</mark>"
                  ],
                  "fragment_size" : 70

                }
              }
            }

          }
        };
      }
      

      if (<?php echo $id_role['id_role']; ?> == 2) {
          var body2 = {
           index: self.index,
           type: self.type,
           body: {
            query: {
              bool: {
                must: [
                {
                  query_string: {
                    query: self.q()
                  }
                },
                {
                  term: { "teacher_id" : <?php echo $id_user; ?> }
                }
                ]
                
              } 
              

            },
            highlight: {
              require_field_match: false,
              fields: {
                "*": {
                  "pre_tags": [
                  "<mark>"
                  ],
                  "post_tags": [
                  "</mark>"
                  ],
                  "fragment_size" : 70

                }
              }
            }

          }
        };
      }
      

        self.client.search(body2).then(function(body2){

          self.hits(body2.hits.hits.map(function(hit){ return new Hit(hit); }));

        }, alert);


      }

      self.mappings = {
        index: self.index,
        body: {
          mappings: {}
        }
      };

      self.mappings.body.mappings[self.type] = {
        properties: {
          filename: {type: 'text'},
          content: {
            type: 'attachment',
            fields: {
              content:       {store: 'yes'},
              author:        {store: 'yes'},
              title:         {store: 'yes'},
              date:          {store: 'yes'},
              keywords:      {store: 'yes'}
            }
          },
          teacher_id: {type: 'integer'},
          student_id: {type: 'integer'},
          teacher_name: {type: 'text'},
          student_name: {type: 'text'},
          subject: {type: 'text'},
          year: {type: 'text'},
          program: {type: 'text'},
          url: {type: 'text'}
        }
      };

      self.client.indices.exists({index: self.index}, function(body, exists){
        if(!exists) {
          self.client.indices.create(self.mappings);
        } else {
          self.reload();
        }
      });
    }

    var model = new Model();
    ko.applyBindings(model);
  </script>
