



<section class="mb-4">
    <div class="container">
         <div class="px-2 py-4 px-md-4 py-md-3 bg-white shadow-sm rounded">
              <div class="d-flex mb-3 align-items-baseline border-bottom">
                    <h3 class="h5 fw-700 mb-0">
                        <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">{{ translate('Products') }}</span>
                    </h3>
                </div>
                
                
                 
                 
                 
                 
                 
                 
                 
  
       
       <div class="row">

                    @foreach ($featured_productss as $key => $product)
                    <div class="col-md-6 col-sm-6 col-xs-6 col-lg-3  ">
                       <div class="carousel-box">
                        @include('frontend.partials.product_box_1',['product' => $product])
                        </div>
              
                      </div>
                    @endforeach
                    
                    </div>
              
            
            
            
            

       
       
        
             <div class="row">
           
               <div class="col-md-4"></div>
               <div class="col-md-4">
    <div class=" col text-center">
     
      <a href="{{route('search')}}" class=" btn btn-primary btn-sm"  >Show More </a>
    </div>
                 
               </div>
               <div class="col-md-4"></div>
              
               
           
           </div>
          
       </div>
    
   

</div>
         
</section>   
  