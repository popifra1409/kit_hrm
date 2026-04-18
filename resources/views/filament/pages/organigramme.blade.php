<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Organigramme de l'Hôpital</h2>

            <div id="organigramme" class="w-full" style="min-height: 600px;"></div>
        </div>
    </div>

    @push('scripts')
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        // Données de l'organigramme
        const orgData = {
            !!$orgData!!
        };

        // Initialiser l'organigramme
        document.addEventListener('DOMContentLoaded', function() {
            drawOrgChart(orgData);
        });

        function drawOrgChart(data) {
            const width = document.getElementById('organigramme').offsetWidth;
            const height = 600;

            const svg = d3.select("#organigramme")
                .append("svg")
                .attr("width", width)
                .attr("height", height)
                .append("g")
                .attr("transform", "translate(40,0)");

            const root = d3.hierarchy(data);
            const treeLayout = d3.tree().size([height - 100, width - 160]);
            treeLayout(root);

            svg.selectAll('.link')
                .data(root.links())
                .enter()
                .append('path')
                .attr('class', 'link')
                .attr('d', d3.linkHorizontal()
                    .x(d => d.y)
                    .y(d => d.x))
                .attr('fill', 'none')
                .attr('stroke', '#ccc')
                .attr('stroke-width', 2);

            const nodes = svg.selectAll('.node')
                .data(root.descendants())
                .enter()
                .append('g')
                .attr('class', 'node')
                .attr('transform', d => `translate(${d.y},${d.x})`);

            nodes.append('circle')
                .attr('r', 6)
                .attr('fill', d => {
                    switch (d.data.type) {
                        case 'direction':
                            return '#3b82f6';
                        case 'sub_direction':
                            return '#f59e0b';
                        case 'department':
                            return '#10b981';
                        case 'service':
                            return '#8b5cf6';
                        case 'service_medical':
                            return '#06b6d4';
                        default:
                            return '#6b7280';
                    }
                });

            nodes.append('text')
                .attr('dx', 10)
                .attr('dy', 5)
                .style('font-size', '12px')
                .style('font-weight', d => d.depth === 0 ? 'bold' : 'normal')
                .text(d => d.data.name);

            nodes.filter(d => d.data.director || d.data.head)
                .append('text')
                .attr('dx', 10)
                .attr('dy', 18)
                .style('font-size', '10px')
                .style('fill', '#6b7280')
                .text(d => d.data.director || d.data.head);
        }
    </script>

    <style>
        #organigramme {
            overflow-x: auto;
        }

        .node circle {
            cursor: pointer;
            stroke: #fff;
            stroke-width: 2px;
        }

        .node text {
            font-family: sans-serif;
        }

        .link {
            transition: stroke 0.3s;
        }

        .link:hover {
            stroke: #3b82f6;
            stroke-width: 3px;
        }
    </style>
    @endpush
</x-filament-panels::page>