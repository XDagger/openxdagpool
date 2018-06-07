"use strict";

(function(root)
{
	var View = function(type, json)
	{
		if (!(this instanceof View))
			throw Error('View must be instanitated with `new`');

		this.type = type;
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
			$('#graph').append('<p>No data yet, check back soon! ;-)</p><hr>');
			return;
		}

		c3.generate({
			bindto: '#graph',
			data: {
				json: this.json,
				x: 'x',
				xFormat: this.type == 'daily' ? '%Y-%m-%d' : '%m-%d %H:%M',
				type: this.type == 'daily' ? 'bar' : 'area-spline'
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
						format: this.type == 'daily' ? '%Y-%m-%d' : '%m-%d %H:%M'
					}
				}
			}
		});
	}

	module.exports = View;
})(this);
