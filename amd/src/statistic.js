/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 * File documentation for amd/src/statistic.js.
 *
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen, Friederike Schwager (see README.md)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * @package   mod_pdfworkspace
 * @copyright 2018 RWTH Aachen, Friederike Schwager (see README.md)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
import Chart from 'core/chartjs';


export function init( names, otherquestions, myquestions, otheranswers, myanswers, otherprivate, myprivate, otherprotectedquestions, myprotectedquestions, otherprotectedanswers, myprotectedanswers) {
        // On small screens set width depending on number of annotators. Otherwise the diagram is very small.
        let width = Math.max(names.length * 25, 300);
        width = names.length * 40;
        if (window.innerWidth < width) {
            document.getElementById('chart-container').style.width = width + "px";
        }

        var maxValue = calculateMax(otherquestions, myquestions, otheranswers, myanswers, otherprivate, myprivate, otherprotectedquestions, myprotectedquestions, otherprotectedanswers, myprotectedanswers);

        var borderCol = 'rgb(250, 245, 235)';
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: names,
                datasets: [{
                        label: M.util.get_string('mypublicquestions', 'pdfworkspace'),
                        stack: 'questions',
                        data: myquestions,
                        backgroundColor: 'rgb(0,84,159)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    }, {
                        label: M.util.get_string('publicquestions', 'pdfworkspace') + ' ' + M.util.get_string('by_other_users', 'pdfworkspace'),
                        stack: 'questions',
                        data: otherquestions,
                        backgroundColor: 'rgb(142,186,229)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    }, {
                        label: M.util.get_string('myprotectedquestions', 'pdfworkspace'),
                        stack: 'questions',
                        data: myprotectedquestions,
                        backgroundColor: 'rgb(0, 152, 161)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    }, {
                        label: M.util.get_string('protected_questions', 'pdfworkspace') + ' ' + M.util.get_string('by_other_users', 'pdfworkspace'),
                        stack: 'questions',
                        data: otherprotectedquestions,
                        backgroundColor: 'rgb(137, 204, 207)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    },
                    {
                        label: M.util.get_string('mypublicanswers', 'pdfworkspace'),
                        stack: 'answers',
                        data: myanswers,
                        backgroundColor: 'rgb(87, 171, 39)',
                        borderColor: 'rgb(0, 0, 0)',
                        borderWidth: 1,
                        borderColor: borderCol,
                        borderWidth: 1,
                    }, 
                    {
                        label: M.util.get_string('publicanswers', 'pdfworkspace') + ' ' + M.util.get_string('by_other_users', 'pdfworkspace'),
                        stack: 'answers',
                        data: otheranswers,
                        backgroundColor: 'rgb(184, 214, 152)',
                        borderColor: 'rgb(0, 0, 0)',
                        borderWidth: 1,
                        borderColor: borderCol,
                        borderWidth: 1,
                    }, 
                    {
                        label: M.util.get_string('myprotectedanswers', 'pdfworkspace'),
                        stack: 'answers',
                        data: myprotectedanswers,
                        backgroundColor: 'rgb(189, 205, 0)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    }, {
                        label: M.util.get_string('protected_answers', 'pdfworkspace') + ' ' + M.util.get_string('by_other_users', 'pdfworkspace'),
                        stack: 'answers',
                        data: otherprotectedanswers,
                        backgroundColor: 'rgb(224, 230, 154)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    },
                    {
                        label: M.util.get_string('myprivate', 'pdfworkspace'),
                        stack: 'private',
                        data: myprivate,
                        backgroundColor: 'rgb(246, 168, 0)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    },
                    {
                        label: M.util.get_string('private_comments', 'pdfworkspace') + ' ' + M.util.get_string('by_other_users', 'pdfworkspace'),
                        stack: 'private',
                        data: otherprivate,
                        backgroundColor: 'rgb(253, 212, 143)',
                        borderColor: borderCol,
                        borderWidth: 1,
                    }]
            },
            options: {
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: M.util.get_string('chart_title', 'pdfworkspace'),
                    fontSize: 20
                },
                legend: {
                    display: true,
                    position: 'bottom'
                },
                scales: {
                    x: {
                            stacked: true,
                            ticks: {
                                autoSkip: false,
                            },
                        },
                    y: {
                            ticks: {
                                beginAtZero: true,
                                precision: 0,
                                max: maxValue + 2,
                            },
                        },
                },
                tooltips: {
                    mode: 'x'
                },
                layout: {
                    padding: {
                        left: 0,
                        right: 0,
                        top: 0,
                        bottom: 0
                    }
                }
            }
        });

}

/**
 * Calculate the height of the diagramm in the statistic
 */
function calculateMax(otherquestions, myquestions, otheranswers, myanswers, otherprivate, myprivate, otherprotectedquestions, myprotectedquestions, otherprotectedanswers, myprotectedanswers) {
    let max = 0;
    for (let i = 0; i < otherquestions.length; ++i) {
        if (otherquestions[i] + myquestions[i] + otherprotectedquestions[i] + myprotectedquestions[i] > max) {
            max = otherquestions[i] + myquestions[i] + otherprotectedquestions[i] + myprotectedquestions[i];
        }
        if (otheranswers[i] + myanswers[i] + otherprotectedanswers[i] + myprotectedanswers[i] > max) {
            max = otheranswers[i] + myanswers[i] + otherprotectedanswers[i] + myprotectedanswers[i];
        }
        if (otherprivate[i] + myprivate[i] > max) {
            max = otherprivate[i] + myprivate[i];
        }
    } 

    return max;
}
