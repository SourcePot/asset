import("https://cdn.jsdelivr.net/npm/@observablehq/plot@0.6/+esm").then((Plot)=>{
    import("https://cdn.jsdelivr.net/npm/d3@7/+esm").then((d3)=>{
        var plotDef={
            x:{},
            y:{grid: true},
            marks: [
                Plot.ruleY([0]),
                Plot.areaY(data, {x: "Date", y: "Amount", fillOpacity: 0.2}),
                Plot.lineY(data, {x: "Date", y: "Amount"})
              ],
            marginLeft: 70    
            };
        const plot=Plot.plot(plotDef);
        jQuery('[id=myplot]').html(plot);
    });
});