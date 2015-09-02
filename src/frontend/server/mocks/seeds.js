module.exports = function(app) {
  var express = require('express');
  var seedsRouter = express.Router();

  seedsRouter.get('/', function(req, res) {
    res.send({
      'seeds': [
      { id: 1, name: 'girassol', sci_name: 'girassolum', img_url: 'images/1.jpg', desc: 'descrição ipsoluma'},
      { id: 2, name: 'girassol', sci_name: 'girassolum', img_url: 'images/2.jpg', desc: 'descrição ipsoluma'},
      { id: 3, name: 'girassol', sci_name: 'girassolum', img_url: 'images/3.jpg', desc: 'descrição ipsoluma'},
      { id: 4, name: 'girassol', sci_name: 'girassolum', img_url: 'images/4.jpg', desc: 'descrição ipsoluma'},

      ]
    });
  });

  seedsRouter.post('/', function(req, res) {
    res.status(201).end();
  });

  seedsRouter.get('/:id', function(req, res) {
    res.send({
      'seeds': {
        id: req.params.id
      }
    });
  });

  seedsRouter.put('/:id', function(req, res) {
    res.send({
      'seeds': {
        id: req.params.id
      }
    });
  });

  seedsRouter.delete('/:id', function(req, res) {
    res.status(204).end();
  });

  app.use('/api/seeds', seedsRouter);
};
