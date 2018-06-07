"use strict";

(function(root)
{
	var View = function(json)
	{
		if (!(this instanceof View))
			throw Error('View must be instanitated with `new`');

		this.json = JSON.parse(json);
		this.registerHandlers();
	}

	View.prototype.registerHandlers = function()
	{
		$(document).ready(this.initGraph.bind(this));
	}

	View.prototype.initGraph = function()
	{
		if (this.json.x.length == 0) {
			$('#graph').append('<p>No payouts yet, check back soon! ;-)</p><hr>');
			return;
		}

		c3.generate({
			bindto: '#graph',
			data: {
				json: this.json,
				x: 'x',
				xFormat: '%Y-%m-%d',
				type: 'bar'
			},
			point: {
				show: false
			},
			color: {
				pattern: ['#778899', '#FF00FF', '#48D1CC']
			},
			axis: {
				x: {
					type: 'timeseries',
					tick: {
						format: '%Y-%m-%d'
					}
				}
			}
		});
	}

	module.exports = View;
})(this);
