(function ($) {
    $.fn.canvasAreaDraw = function (options) {
        this.each(function (index, element) {
            init.apply(element, [index, element, options]);
        });
    }

    var init = function (index, input, options) {
        var points, activePoint, settings;
        var $reset, $zoom_in, $zoom_out, $canvas, ctx, image;
        var draw, mousedown, stopdrag, move, zoom_in, zoom_out, moveall, resize, reset, rightclick, record;
        var dragpoint;
        var startpoint = false;
        settings = $.extend({
            imageUrl: $(this).attr('data-image-url')
        }, options);
        var v = $(input).val().replace(/[^0-9\,]/ig, '');
        if (v.length) {
            points = v.split(',').map(function (point) {
                return parseInt(point, 10);
            });
        } else {
            points = [];
        }
        $reset = $('#btn_reset');
        $zoom_in = $('#btn_zoom_in');
        $zoom_out = $('#btn_zoom_out');
        $canvas = $('#canvas_draw');
        ctx = $canvas[0].getContext('2d');
        image = new Image();
        resize = function () {
            $canvas.attr('height', image.height).attr('width', image.width);
            draw();
        };
        $(image).on('load', resize);
        image.src = settings.imageUrl;
        if (image.loaded) {
            resize();
        }
        $canvas.css({background: 'url(' + image.src + ')'});
        $canvas.css('transform-origin','0 0');
        $canvas.css('transform','scale(1.0)');
        zoom_in = function () {
            var matrix = $canvas.css('transform');
            var values = matrix.split('(')[1];
            values = values.split(')')[0];
            values = values.split(',');
            var a = values[0];
            var b = values[1];
            var scale = Math.sqrt(a*a + b*b);
            scale = scale + 0.1;
            $canvas.css('transform','scale('+scale+')');
        };
        zoom_out = function () {
            var matrix = $canvas.css('transform');
            var values = matrix.split('(')[1];
            values = values.split(')')[0];
            values = values.split(',');
            var a = values[0];
            var b = values[1];
            var scale = Math.sqrt(a*a + b*b);
            scale = scale - 0.1;
            if(scale<1.0) scale=1.0;
            $canvas.css('transform','scale('+scale+')');
        };
        reset = function () {
            points = [];
            draw();
            $reset.addClass('disabled');
            $('#btn_apply').addClass('disabled');
            $('#msg_drawing').show();
        };
        move = function (e) {
            if (!e.offsetX) {
                e.offsetX = (e.pageX - $(e.target).offset().left);
                e.offsetY = (e.pageY - $(e.target).offset().top);
            }
            points[activePoint] = Math.round(e.offsetX);
            points[activePoint + 1] = Math.round(e.offsetY);
            draw();
        };
        moveall = function (e) {
            if (!e.offsetX) {
                e.offsetX = (e.pageX - $(e.target).offset().left);
                e.offsetY = (e.pageY - $(e.target).offset().top);
            }
            if (!startpoint) {
                startpoint = {x: Math.round(e.offsetX), y: Math.round(e.offsetY)};
            }
            var sdvpoint = {x: Math.round(e.offsetX), y: Math.round(e.offsetY)};
            for (var i = 0; i < points.length; i++) {
                points[i] = (sdvpoint.x - startpoint.x) + points[i];
                points[++i] = (sdvpoint.y - startpoint.y) + points[i];
            }
            startpoint = sdvpoint;
            draw();
        };
        stopdrag = function () {
            $(this).off('mousemove');
            record();
            activePoint = null;
        };
        rightclick = function (e) {
            e.preventDefault();
            if (!e.offsetX) {
                e.offsetX = (e.pageX - $(e.target).offset().left);
                e.offsetY = (e.pageY - $(e.target).offset().top);
            }
            var x = e.offsetX, y = e.offsetY;
            for (var i = 0; i < points.length; i += 2) {
                dis = Math.sqrt(Math.pow(x - points[i], 2) + Math.pow(y - points[i + 1], 2));
                if (dis < 6) {
                    points.splice(i, 2);
                    if(points.length<6) {
                        $('#btn_apply').addClass('disabled');
                    }
                    if(points.length==0) {
                        $reset.addClass('disabled');
                    }
                    draw();
                    record();
                    return false;
                }
            }
            return false;
        };
        mousedown = function (e) {
            $('#msg_drawing').hide();
            var x, y, dis, lineDis, insertAt = points.length;
            if (e.which === 3) {
                return false;
            }
            e.preventDefault();
            if (!e.offsetX) {
                e.offsetX = (e.pageX - $(e.target).offset().left);
                e.offsetY = (e.pageY - $(e.target).offset().top);
            }
            x = e.offsetX;
            y = e.offsetY;
            if (points.length >= 6) {
                var c = getCenter();
                ctx.fillRect(c.x - 4, c.y - 4, 8, 8);
                dis = Math.sqrt(Math.pow(x - c.x, 2) + Math.pow(y - c.y, 2));
                if (dis < 6) {
                    startpoint = false;
                    $(this).on('mousemove', moveall);
                    return false;
                }
            }
            for (var i = 0; i < points.length; i += 2) {
                dis = Math.sqrt(Math.pow(x - points[i], 2) + Math.pow(y - points[i + 1], 2));
                if (dis < 6) {
                    activePoint = i;
                    $(this).on('mousemove', move);
                    return false;
                }
            }
            for (var i = 0; i < points.length; i += 2) {
                if (i > 1) {
                    lineDis = dotLineLength(
                        x, y,
                        points[i], points[i + 1],
                        points[i - 2], points[i - 1],
                        true
                    );
                    if (lineDis < 6) {
                        insertAt = i;
                    }
                }
            }
            points.splice(insertAt, 0, Math.round(x), Math.round(y));
            activePoint = insertAt;
            $(this).on('mousemove', move);
            draw();
            record();
            $reset.removeClass('disabled');
            return false;
        };

        draw = function () {
            ctx.canvas.width = ctx.canvas.width;
            record();
            if (points.length < 2) {
                return;
            }
            ctx.globalCompositeOperation = 'destination-over';
            ctx.fillStyle = 'rgb(255,255,255)';
            ctx.strokeStyle = 'rgb(255,20,20)';
            ctx.lineWidth = 1;
            if (points.length >= 6) {
                var c = getCenter();
                ctx.fillRect(c.x - 4, c.y - 4, 8, 8);
            }
            ctx.beginPath();
            ctx.moveTo(points[0], points[1]);
            for (var i = 0; i < points.length; i += 2) {
                ctx.fillRect(points[i] - 2, points[i + 1] - 2, 4, 4);
                ctx.strokeRect(points[i] - 2, points[i + 1] - 2, 4, 4);
                if (points.length > 2 && i > 1) {
                    ctx.lineTo(points[i], points[i + 1]);
                }
            }
            ctx.closePath();
            ctx.fillStyle = 'rgba(255,0,0,0.3)';
            ctx.fill();
            ctx.stroke();
        };

        record = function () {
            $(input).val(points.join(','));
        };

        getCenter = function () {
            $('#btn_apply').removeClass('disabled');
            var ptc = [];
            for (i = 0; i < points.length; i++) {
                ptc.push({x: points[i], y: points[++i]});
            }
            var first = ptc[0], last = ptc[ptc.length - 1];
            if (first.x != last.x || first.y != last.y) ptc.push(first);
            var twicearea = 0,
                x = 0, y = 0,
                nptc = ptc.length,
                p1, p2, f;
            for (var i = 0, j = nptc - 1; i < nptc; j = i++) {
                p1 = ptc[i];
                p2 = ptc[j];
                f = p1.x * p2.y - p2.x * p1.y;
                twicearea += f;
                x += ( p1.x + p2.x ) * f;
                y += ( p1.y + p2.y ) * f;
            }
            f = twicearea * 3;
            return {x: x / f, y: y / f};
        };

        $(input).on('change', function () {
            var v = $(input).val().replace(/[^0-9\,]/ig, '');
            if (v.length) {
                points = v.split(',').map(function (point) {
                    return parseInt(point, 10);
                });
            } else {
                points = [];
            }
            draw();
        });
        $(document).find($reset).click(reset);
        $(document).find($zoom_in).click(zoom_in);
        $(document).find($zoom_out).click(zoom_out);
        $(document).find($canvas).on('mousedown', mousedown);
        $(document).find($canvas).on('contextmenu', rightclick);
        $(document).find($canvas).on('mouseup', stopdrag);
    };

    var dotLineLength = function (x, y, x0, y0, x1, y1, o) {
        function lineLength(x, y, x0, y0) {
            return Math.sqrt((x -= x0) * x + (y -= y0) * y);
        }
        if (o && !(o = function (x, y, x0, y0, x1, y1) {
                if (!(x1 - x0)) return {x: x0, y: y};
                else if (!(y1 - y0)) return {x: x, y: y0};
                var left, tg = -1 / ((y1 - y0) / (x1 - x0));
                return {
                    x: left = (x1 * (x * tg - y + y0) + x0 * (x * -tg + y - y1)) / (tg * (x1 - x0) + y0 - y1),
                    y: tg * left - tg * x + y
                };
            }(x, y, x0, y0, x1, y1), o.x >= Math.min(x0, x1) && o.x <= Math.max(x0, x1) && o.y >= Math.min(y0, y1) && o.y <= Math.max(y0, y1))) {
            var l1 = lineLength(x, y, x0, y0), l2 = lineLength(x, y, x1, y1);
            return l1 > l2 ? l2 : l1;
        }
        else {
            var a = y0 - y1, b = x1 - x0, c = x0 * y1 - y0 * x1;
            return Math.abs(a * x + b * y + c) / Math.sqrt(a * a + b * b);
        }
    };
})(jQuery);

