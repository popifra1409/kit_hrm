<x-filament-panels::page>
    <x-filament::card>
        <div class="mb-4 flex justify-between items-center">
            <h3 class="text-lg font-medium">Organigramme Hiérarchique</h3>
            <div class="space-x-2">
                <x-filament::button color="secondary" id="expandAll">
                    Tout développer
                </x-filament::button>
                <x-filament::button color="secondary" id="collapseAll">
                    Tout réduire
                </x-filament::button>
            </div>
        </div>

        <div id="organigramme"
            style="width: 100%; height: 800px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
            <!-- L'organigramme sera généré ici -->
        </div>
    </x-filament::card>

    @push('scripts')
        <script src="https://d3js.org/d3.v7.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const data = @json(json_decode($this->getOrgData()));

                // Configuration
                const width = 1200;
                const height = 800;
                const nodeWidth = 200;
                const nodeHeight = 80;
                const nodeSpacing = 50;

                // Créer le SVG
                const svg = d3.select("#organigramme")
                    .append("svg")
                    .attr("width", width)
                    .attr("height", height);

                const g = svg.append("g")
                    .attr("transform", "translate(40,40)");

                // Créer la hiérarchie
                const root = d3.hierarchy(data);
                const treeLayout = d3.tree()
                    .size([width - 100, height - 100])
                    .nodeSize([nodeWidth + nodeSpacing, nodeHeight + nodeSpacing]);

                treeLayout(root);

                // Dessiner les liens
                g.selectAll(".link")
                    .data(root.links())
                    .enter()
                    .append("path")
                    .attr("class", "link")
                    .attr("fill", "none")
                    .attr("stroke", "#3b82f6")
                    .attr("stroke-width", 2)
                    .attr("d", d3.linkVertical()
                        .x(d => d.x)
                        .y(d => d.y));

                // Créer les groupes de nœuds
                const nodes = g.selectAll(".node")
                    .data(root.descendants())
                    .enter()
                    .append("g")
                    .attr("class", "node")
                    .attr("transform", d => `translate(${d.x},${d.y})`);

                // Ajouter les rectangles
                nodes.append("rect")
                    .attr("width", nodeWidth)
                    .attr("height", nodeHeight)
                    .attr("x", -nodeWidth / 2)
                    .attr("y", -nodeHeight / 2)
                    .attr("rx", 8)
                    .attr("fill", d => {
                        if (d.depth === 0) return "#dc2626"; // Rouge pour établissement
                        if (d.depth === 1) return "#7c3aed"; // Violet pour PCA
                        if (d.depth === 2) return "#2563eb"; // Bleu pour DG
                        if (d.depth === 3) return "#0891b2"; // Cyan pour DGA
                        return "#059669"; // Vert pour les autres
                    })
                    .attr("stroke", "#1f2937")
                    .attr("stroke-width", 2);

                // Ajouter le nom
                nodes.append("text")
                    .attr("dy", -10)
                    .attr("text-anchor", "middle")
                    .attr("fill", "white")
                    .attr("font-weight", "bold")
                    .attr("font-size", "14px")
                    .text(d => d.data.name);

                // Ajouter le titre
                nodes.append("text")
                    .attr("dy", 10)
                    .attr("text-anchor", "middle")
                    .attr("fill", "white")
                    .attr("font-size", "12px")
                    .text(d => d.data.title)
                    .call(wrap, nodeWidth - 20);

                // Fonction pour wrapper le texte
                function wrap(text, width) {
                    text.each(function() {
                        var text = d3.select(this),
                            words = text.text().split(/\s+/).reverse(),
                            word,
                            line = [],
                            lineNumber = 0,
                            lineHeight = 1.1,
                            y = text.attr("y") || 0,
                            dy = parseFloat(text.attr("dy")),
                            tspan = text.text(null).append("tspan").attr("x", 0).attr("y", y).attr("dy", dy +
                                "px");

                        while (word = words.pop()) {
                            line.push(word);
                            tspan.text(line.join(" "));
                            if (tspan.node().getComputedTextLength() > width) {
                                line.pop();
                                tspan.text(line.join(" "));
                                line = [word];
                                tspan = text.append("tspan")
                                    .attr("x", 0)
                                    .attr("y", y)
                                    .attr("dy", ++lineNumber * lineHeight + dy + "px")
                                    .text(word);
                            }
                        }
                    });
                }

                // Boutons expand/collapse
                document.getElementById('expandAll').addEventListener('click', function() {
                    nodes.style('display', 'block');
                });

                document.getElementById('collapseAll').addEventListener('click', function() {
                    nodes.filter(d => d.depth > 2).style('display', 'none');
                });
            });
        </script>
    @endpush

    <style>
        #organigramme svg {
            background: #f9fafb;
        }

        .node {
            cursor: pointer;
        }

        .node:hover rect {
            filter: brightness(1.1);
        }
    </style>
</x-filament-panels::page>
